<?php

namespace App\Services;

class RequirementFileService
{
    public function getKind(array $requirement, int $index = 0): string
    {
        $type = strtolower((string) ($requirement['type'] ?? $requirement['file_type'] ?? ''));

        if (in_array($type, ['image', 'foto', 'gambar'], true)) {
            return 'image';
        }

        if (in_array($type, ['document', 'dokumen', 'formulir'], true)) {
            return 'document';
        }

        $descriptor = strtolower(implode(' ', [
            $requirement['key'] ?? '',
            $requirement['label'] ?? '',
            $requirement['name'] ?? '',
        ]));

        if (preg_match('/(foto|gambar|image|kamera|camera|dokumentasi|photo)/', $descriptor)) {
            return 'image';
        }

        if (preg_match('/(formulir|\bdokumen\b|document|data[_\s-]?dukung|pendukung|pdf|word|docx?|berkas)/', $descriptor)) {
            return 'document';
        }

        return $index % 2 === 0 ? 'image' : 'document';
    }

    public function rulesFor(array $requirement, int $index = 0): array
    {
        return $this->getKind($requirement, $index) === 'image'
            ? ['file', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:5120']
            : ['file', 'mimes:pdf,doc,docx', 'max:5120'];
    }
}
