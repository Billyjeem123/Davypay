<?php

namespace App\Http\Controllers\v1\Webhook;

use App\Helpers\KycLogger;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Kyc;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Notifications\KycFailedNotification;
use App\Notifications\TierThreeUpgradeNotifcation;
use App\Notifications\TierTwoUpgradeNotifcation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DojahWebhookController extends Controller
{

    public function dojahWebhook(Request $request)
    {
        try {
            $payload = $request->all();
            KycLogger::log("dojah webhook received", $payload);

            $metadata = $payload['metadata'] ?? [];
            $data = $payload['data'] ?? [];

            $email = $metadata['email'] ?? null;
            $userIdentifier = $metadata['user_id'] ?? null;

            #  Normalize tier (e.g. "Tier 2" -> "tier_2")
            $rawTier = $metadata['tier'] ?? null;
            $normalizedTier = $rawTier ? strtolower(str_replace(' ', '_', $rawTier)) : null;

            #  Only process tier_2 or tier_3
            if (!in_array($normalizedTier, ['tier_2', 'tier_3'])) {
                KycLogger::log("Unsupported tier received", ['tier' => $normalizedTier]);
                return response('Tier not supported', 200);
            }

            #  Find user by ID or email
            $user = User::where('id', $userIdentifier)
                ->orWhere('email', $email)
                ->first();

            if (!$user) {
                KycLogger::log("User not found for KYC webhook", ['email' => $email, 'user_id' => $userIdentifier]);
                return response('User not found', 200);
            }

            $verificationStatus = $payload['verification_status'] ?? null;
            $reason = $payload['message'] ?? 'Unknown reason';
            if ($verificationStatus !== 'Completed') {
                return $this->handleFailedKyc($user, $verificationStatus, $payload, $rawTier, $reason);
            }
            $entity = $data['government_data']['data']['bvn']['entity'] ?? [];

            $bvn         = $entity['bvn'] ?? null;
            $nin         = $entity['nin'] ?? null;
            $dob         = $entity['date_of_birth'] ?? null;
            $phone       = $entity['phone_number1'] ?? null;
            $nationality = $entity['nationality'] ?? null;
            $address     = $entity['residential_address'] ?? null;

            $selfieUrl         = $payload['selfie_url'] ?? null;
            $verificationImage = $payload['verification_url'] ?? null;
            $verificationStatus = $payload['verification_status'] ?? null;

            #  Normalize verification status
            if ($verificationStatus === 'Completed') {
                $verificationStatus = 'approved';
            }

            $zipcode = $metadata['ipinfo']['zip'] ?? null;

            $verificationField = $payload['verification_type'] ?? null;

            $kycData = [
                'webhook'            => $payload,
                'bvn'                => $bvn,
                'nin'                => $nin,
                'dob'                => $dob,
                'phone_number'       => $phone,
                'nationality'        => $nationality,
                'address'            => $address,
                'tier'               => $normalizedTier,
                'selfie'             => $selfieUrl,
                'selfie_image'       => $selfieUrl,
                'verification_image' => $verificationImage,
                'zipcode'            => $zipcode,
                'status'             => $verificationStatus,
            ];

            if ($normalizedTier === 'tier_3') {
                return $this->processTierThreeUpgrade($payload);
            }

            Kyc::updateOrCreate(
                ['user_id' => $user->id],
                $kycData
            );

            $user->update([
                'first_name'    => $entity['first_name'] ?? $user->first_name,
                'last_name'     => $entity['last_name'] ?? $user->last_name,
                'account_level' => $normalizedTier,
                'kyc_status'    => 'verified',
                'kyc_type'      => $verificationField,
            ]);


            if ($user->wallet && $user->wallet->has_exceeded_limit) {
                $user->wallet->update([
                    'has_exceeded_limit' => false,
                    'status' => 'active',
                ]);

                KycLogger::log("Wallet limit status updated", [
                    'user_id' => $user->id,
                    'was_restricted' => $user->wallet->has_exceeded_limit,
                ]);
            }

            #  Send notification only if tier is tier_2 AND status is approved
            if ($normalizedTier === 'tier_2' && $verificationStatus === 'approved') {
                $user->notify(new TierTwoUpgradeNotifcation($user));
            }



            KycLogger::log("Tier_2 verification successful for user", ['user' => $user->email]);

            $this->track(
                'Tier_2 verification',
                "$verificationField verification successful for {$verificationStatus}",
                [
                    'user_id' => $user->id,
                    'nin' => $nin,
                    'status' => 'passed_api_check',
                    'effective' => true,
                ]
            );

            return response('KYC processed successfully', 200);

        } catch (\Exception $e) {
            KycLogger::log('KYC Webhook processing failed', [
                'error' => $e->getMessage(),
            ]);
            return response('Internal server error', 500);
        }
    }


        private function handleFailedKyc(User $user, string $verificationStatus, array $payload,  string $rawTier, string $reason)
    {
        // Mark KYC as failed
        Kyc::updateOrCreate(
            ['user_id' => $user->id],
            [
                'webhook' => $payload,
                'status'  => 'failed',
            ]
        );
        $user->notify(new  KycFailedNotification($user, $verificationStatus, $rawTier, $reason));
        KycLogger::log("KYC verification failed for ", [
            'user_id' => $user->email,
            'status'  => $verificationStatus,
        ]);


        $this->track(
            "$rawTier verification",
            "$rawTier verification failed - Status: {$verificationStatus}",
            [
                    'user_id' => $user->id,
                    'status' => 'passed_api_check',
                    'effective' => true,
            ]
        );

        return response('KYC failed, notification sent', 200);
    }


    public function processTierThreeUpgrade($payload)
    {
        try {
            $metadata = $payload['metadata'] ?? [];
            $data = $payload['data'] ?? [];

            KycLogger::log("Tier three upgrade processing started", [
                'user_email' => $metadata['email'] ?? 'unknown',
                'user_id' => $metadata['user_id'] ?? 'unknown'
            ]);

            $email = $metadata['email'] ?? null;
            $userIdentifier = $metadata['user_id'] ?? null;

            #  Find user by ID or email
            $user = User::where('id', $userIdentifier)
                ->orWhere('email', $email)
                ->first();

            if (!$user) {
                KycLogger::log("User not found for Tier 3 KYC webhook", [
                    'email' => $email,
                    'user_id' => $userIdentifier
                ]);
                return response('User not found', 200);
            }

            #  Extract ID document data
            $idData = $data['id']['data']['id_data'] ?? [];
            $addressData = $data['address']['data'] ?? [];
            $locationData = $addressData['location'] ?? [];

            #  Extract verification details
            $verificationStatus = $payload['verification_status'] ?? null;
            $normalizedStatus = ($verificationStatus === 'Completed') ? 'approved' : strtolower($verificationStatus);

            #  Fetch existing KYC record if any
            $existingKyc = Kyc::where('user_id', $user->id)->first();

            #  Merge existing webhook data if available
            $existingWebhookData = $existingKyc?->webhook ?? [];
            if (is_string($existingWebhookData)) {
                $existingWebhookData = json_decode($existingWebhookData, true);
            }
            $mergedWebhook = array_merge($existingWebhookData ?? [], $payload);

            #  Prepare Tier 3 KYC data
            $tier3KycData = [
                'webhook' => $mergedWebhook,
                'tier' => 'tier_3',
                'status' => $normalizedStatus,

                #  Document information
                'document_type' => $idData['document_type'] ?? null,
                'document_number' => $idData['document_number'] ?? null,
                'date_issued' => $idData['date_issued'] ?? null,
                'expiry_date' => $idData['expiry_date'] ?? null,
                'middle_name' => $idData['middle_name'] ?? null,
                'mrz_status' => $idData['mrz_status'] ?? null,

                #  Image URLs
                'id_image_url' => $payload['id_url'] ?? null,
                'id_back_url' => $payload['back_url'] ?? null,
                'selfie_image' => $payload['selfie_url'] ?? null,
                'verification_image' => $payload['verification_url'] ?? null,

                #  Verification details
                'reference_id' => $payload['reference_id'] ?? null,
                'widget_id' => $payload['widget_id'] ?? null,
                'verification_mode' => $payload['verification_mode'] ?? null,
                'verification_type' => $payload['verification_type'] ?? null,
                'verification_value' => $payload['verification_value'] ?? null,

                #  AML and address verification
                'aml_status' => isset($payload['aml']['status']) ? (bool) $payload['aml']['status'] : null,
                'address_verification_status' => isset($data['address']['status']) ? (bool) $data['address']['status'] : null,

                #  Location data
                'user_latitude' => $locationData['user_location']['latitude'] ?? null,
                'user_longitude' => $locationData['user_location']['longitude'] ?? null,
                'user_location_name' => $locationData['user_location']['name'] ?? null,
                'address' => $locationData['user_location']['name'] ?? null,
                'address_latitude' => $locationData['address_location']['latitude'] ?? null,
                'address_longitude' => $locationData['address_location']['longitude'] ?? null,

                #  Personal information from document
                'nationality' => $idData['nationality'] ?? null,
                'dob' => $idData['date_of_birth'] ?? null,

                #  Additional data
                'extras' => isset($idData['extras']) ? json_encode($idData['extras']) : null,
                'zipcode' => $metadata['ipinfo']['zip'] ?? null,
            ];

            if ($existingKyc) {
                $mergedData = array_merge($existingKyc->toArray(), $tier3KycData);

                unset($mergedData['id'], $mergedData['created_at'], $mergedData['updated_at']);

                $existingKyc->update($mergedData);

                KycLogger::log("Tier 3 KYC data merged with existing record", [
                    'user_id' => $user->email,
                    'kyc_id' => $existingKyc->id
                ]);
            } else {
                Kyc::create(array_merge($tier3KycData, ['user_id' => $user->id]));

                KycLogger::log("New Tier 3 KYC record created", [
                    'user_id' => $user->email
                ]);
            }

            $user->update([
                'first_name' => $idData['first_name'] ?? $user->first_name,
                'last_name' => $idData['last_name'] ?? $user->last_name,
                'account_level' => 'tier_3',
                'kyc_status' => $normalizedStatus === 'approved' ? 'verified' : 'pending',
                'kyc_type' => $payload['verification_type'],
            ]);

            if ($user->wallet && $user->wallet->has_exceeded_limit) {
                $user->wallet->update([
                    'has_exceeded_limit' => false,
                    'status' => 'active',
                ]);

                KycLogger::log("Wallet limit status updated", [
                    'user_id' => $user->id,
                    'was_restricted' => $user->wallet->has_exceeded_limit,
                ]);
            }

            KycLogger::log("Tier 3 KYC processing completed successfully", [
                'user_id' => $user->id,
                'status' => $normalizedStatus,
                'document_type' => $idData['document_type'] ?? 'unknown'
            ]);

            #  Optional: Notify user
            if ($normalizedStatus === 'approved') {
                $user->notify(new TierThreeUpgradeNotifcation($user));
            }

            KycLogger::log("Tier_3 verification successful for user", ['user' => $user->email]);


            $this->track(
                'Tier_3 verification',
                "$payload[verification_type] verification successful for {$verificationStatus}",
                [
                    'user_id' => $user->id,
                    'status' => 'passed_api_check',
                    'effective' => true,
                ]
            );

            return response('Tier 3 KYC processed successfully', 200);

        } catch (\Exception $e) {
            KycLogger::log('Tier 3 KYC processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_email' => $metadata['email'] ?? 'unknown'
            ]);

            return response('Internal server error', 500);
        }
    }



    public function track(string $activity, string $description = null, array $properties = [])
    {
        UserActivityLog::create([
            'user_id' => Auth::id() ?? $properties['user_id'] ?? null,
            'activity' => $activity,
            'description' => $description ?: $this->getDefaultDescription($activity),
            'page_url' => request()->fullUrl(), #  Use request() helper instead
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()  ?? "null"#  Use
        ]);
    }






}
