<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImgBBService
{
    protected string $apiKey;
    protected string $endpoint;

    public function __construct()
    {
        $this->apiKey = config('imgbb.api_key');
        $this->endpoint = config('imgbb.endpoint');
    }

    public function upload(UploadedFile $file): array
    {
        $imageData = base64_encode(file_get_contents($file->getRealPath()));

        $response = Http::asMultipart()
            ->post($this->endpoint, [
                'key' => $this->apiKey,
                'image' => $imageData,
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('ImgBB upload failed: ' . $response->body());
        }

        $data = $response->json('data');

        return [
            'url' => $data['url'] ?? null,
            'delete_url' => $data['delete_url'] ?? null,
            'display_url' => $data['display_url'] ?? $data['url'] ?? null,
            'size' => $data['size'] ?? 0,
            'mime' => $data['image']['mime'] ?? 'image/gif',
        ];
    }

    public static function generateProxyPath(): string
    {
        return Str::random(12);
    }
}
