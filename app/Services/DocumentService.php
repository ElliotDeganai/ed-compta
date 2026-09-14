<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class DocumentService
{
    public const DISK = 'local';

    public const DIRECTORY = 'documents';

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function attachMany(Model $model, array $files, int $userId): void
    {
        foreach ($files as $file) {
            $this->attach($model, $file, $userId);
        }
    }

    public function attach(Model $model, UploadedFile $file, int $userId): Document
    {
        $path = $file->store(self::DIRECTORY.'/'.$userId, self::DISK);

        return $model->documents()->create([
            'user_id' => $userId,
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
    }
}
