<?php

/**
 * Created By  : Sambit Kumar Dalai
 * Created On  : 20-05-2022
 * Module Name : UserModel Model
 * Description : Managing user authentications.
 **/

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;

class UserModel extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory, HasApiTokens;

    protected $table = 'm_user_master';
    protected $primaryKey = 'intUserId';
    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vchUserName', 'vchUserId',

    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'vchPassword',
    ];
    /**
     * Retrieve the identifier for the JWT key.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Created By   : Sambit Kumar Dalai 
     * Created On   : 20-05-2022
     * Description  : user validation
     **/
    public static function ValidationHandler($formData)
    {
        $validationRules = [
            'userId'   => 'bail|required|min:2|max:15',
            'userType' => 'bail|required',
            'password' => 'bail|required|min:5|max:50'
        ];
        $validationMessages = [
            'userType.required' => 'Please select user type.',
            'userId.required' => 'Please enter user id.',
            'password.required' => 'Please enter password.',
        ];
        $validator = Validator::make($formData, $validationRules, $validationMessages);

        $msg = array();

        if ($validator->fails()) {
            $errors = $validator->errors();
            foreach ($errors->all() as $message) {
                $msg[] = $message;
            }
        }
        return $msg;
    }

    public function findForPassport($username)
    {
        return $this->where('vchUserId', $username)->first();
    }
    public function validateForPassportPasswordGrant($password)
    {
        return Hash::check($password, $this->vchPassword);
    }
}
