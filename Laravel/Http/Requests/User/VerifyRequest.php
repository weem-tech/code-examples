<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Carbon\Carbon;
use Fifth\Generator\Http\Requests\BaseRequest;
use Illuminate\Support\Facades\DB;

class VerifyRequest extends BaseRequest
{
    protected $forbiddenMessages = [
        'notValidCode'  => 'Wrong authorization code'
    ];

    public function init(): void
    {
        $this->checkData = DB::table('email_verifications')
            ->where('token', $this->code)
            ->where('created_at', '>', Carbon::now()->subMinutes(15))
            ->where('active', true)
            ->first();

        if ($this->user_id) {
            $this->user = User::findOrFail($this->user_id);
        } elseif ($this->email) {
            $this->user = User::findOrFailBy('email', $this->email);
        }

        abort_if(!$this->user, '404', 'No query results for model [App\Models\User].');
    }

    public function authorizationRules(): array
    {
        return [
            'notValidCode'  => $this->checkData && $this->user && $this->checkData->email === $this->user->email,
        ];
    }

    public function rules(): array
    {
        return [
            'code'      => 'required',
            'user_id'   => 'nullable|required_without:email|exists_on_model:user,id',
            'email'     => 'nullable|required_without:user_id|exists_on_model:user,email',
        ];
    }

    public function manage(): self
    {
        DB::table('email_verifications')->where('email', $this->user->email)->delete();

        $this->user->update([
            'active'    => true,
        ]);

        $this->user = $this->user->loginAfterVerification($this->user);

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
