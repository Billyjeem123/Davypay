<?php

namespace App\Services;

use App\Helpers\Utility;
use App\Models\Beneficiary;

class BeneficaryService
{


    public function createBeneficiary(array $validatedData)
    {
        $userId = auth()->id();


        return Beneficiary::create([
            'name' => $validatedData['name'] ?? null,
            'phone' => "null",
            'service_type' => $validatedData['service_type'],
            'user_id' => $userId,
            "data" => $validatedData['data']
        ]);
    }

    public function getUserBeneficiaryById(mixed $id)
    {
     return Beneficiary::with('user')->find($id);
    }

    public function getMyBeneficiaries()
    {
        $userId = auth()->id(); #  or Auth::id()

        return Beneficiary::with('user')->where('user_id', $userId)->get();
    }

    public function deleteUserBeneficiary($id): bool
    {
        $userId = auth()->id();

        $beneficiary = Beneficiary::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$beneficiary) {
            return false;
        }

        return $beneficiary->delete();
    }



}
