<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/** Saves a user's new profile picture (every role uses the same rules). */
class ProfilePhotoService
{
    /**
     * @param  array  $user  The session user.
     * @return array  The same user with the new profile_photo, ready for the session.
     */
    public function store(array $user, UploadedFile $file): array
    {
        // The extension comes from the file's real content, never the client's
        // filename — otherwise an image-looking file named "x.php" would be
        // saved as executable PHP inside the public storage folder.
        $filename = ($user['role'] ?? 'user') . '_' . $user['id'] . '_' . time() . '.' . $file->extension();

        $file->storeAs('profile-photos', $filename, 'public');

        DB::table('users')->where('id', $user['id'])->update([
            'profile_photo' => $filename,
            'updated_at' => now(),
        ]);

        $user['profile_photo'] = $filename;

        return $user;
    }
}
