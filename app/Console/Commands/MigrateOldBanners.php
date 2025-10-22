<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateOldBanners extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-old-banners';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $oldBanners = DB::connection('mysql_old')->table('banners')->get();

        foreach ($oldBanners as $old) {
            // Extract filename and generate missing info
            $image = $old->image ?? 'default.jpg';
            $filename = pathinfo($image, PATHINFO_BASENAME);
            $originalName = $filename;
            $filePath = '/storage/banners/' . $filename;
            $imageUrl = asset($filePath);

            // Guess mime type and size defaults
            $mimeType = 'image/jpeg';
            $fileSize = 0;

            // Map status
            $status = ($old->status == '1' || strtolower($old->status) == 'a') ? 'active' : 'inactive';

            DB::connection('mysql')->table('banners')->insert([
                'id'            => $old->id,
                'filename'      => $filename,
                'original_name' => $originalName,
                'image_url'     => $imageUrl,
                'file_path'     => $filePath,
                'file_size'     => $fileSize,
                'mime_type'     => $mimeType,
                'status'        => $status,
                'created_at'    => $old->created_at,
                'updated_at'    => $old->updated_at,
                'deleted_at'    => null,
            ]);
        }
        $this->info('Old banners migrated successfully!');
    }
}
