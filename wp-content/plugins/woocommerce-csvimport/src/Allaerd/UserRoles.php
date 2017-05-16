<?php namespace Allaerd;
/**
 * Created by PhpStorm.
 * User: allaerd
 * Date: 10/08/16
 * Time: 13:09
 */


class UserRoles
{
    public static function getUserRoles()
    {
        $user = wp_get_current_user();

        return $user->roles;
    }

}