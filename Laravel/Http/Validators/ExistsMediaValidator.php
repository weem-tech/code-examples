<?php

namespace App\Http\Validators;

use App\Http\Requests\Attachment\Uploader\VideoUploader;
use App\Models\Attachment;
use Illuminate\Support\Facades\Auth;

class ExistsMediaValidator
{
    public function existsInMedia($attribute, $value, $parameters, $validator): bool
    {
        return Attachment::where('id', $value)
            ->where('user_id', Auth::id())
            ->whereIn('type', [Attachment::TYPES['video'], Attachment::TYPES['audio']])->exists();
    }

    public function mediaDuration($attribute, $value, $parameters, $validator): bool
    {
        $uploader = new VideoUploader($value);

        if (!Auth::user()->isPro() && $uploader->getID3Duration() > $parameters[0]) {
            return false;
        }

        return true;
    }
}
