<?php

namespace App\Http\Controllers;

use LdapRecord\Models\ActiveDirectory\User as ADUser;
use LdapRecord\Container;

class UserCheckController extends Controller
{

    public function checkEmailExists($email)
    {
        $connection = config('ldap.default');
        $user = ADUser::on($connection)->where('mail', '=', $email)->first();

        if ($user) {
            return true;
        } else {
            return false;
        }
    }

    public function resetPassword($email, $newPassword){
        
        // Connection settings
        $adServer = env('AD_SERVER');
        $username = env('AD_DAILYMAILUSER_USERNAME');
        $password = env('AD_DAILYMAILUSER_PASSWORD');

        // Establish a secure connection to AD
        try{
            $ldapConn = ldap_connect($adServer);
        } catch (\Exception $e) {
            return false;
        }
        ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
        
        // Initiate SSL/TLS
        try{
            ldap_start_tls($ldapConn);
        } catch (\Exception $e) {
            return false;
        }

        // Bind to AD using a service account
        try{
            ldap_bind($ldapConn, $username, $password);
        } catch (\Exception $e) {
            return false;
        }

        // User DN to modify
        $connection = config('ldap.default');
        $user = ADUser::on($connection)->where('mail', '=', $email)->first();
        if(!$user){
            return false;
        }
        if(!isset($user['distinguishedname'])){
            return false;
        }else{
            if(!isset($user['distinguishedname'][0])){
                return false;
            }
        }
        $userDn = $user['distinguishedname'][0];

        // Prepare the new password
        $newPassword = '"' . $newPassword . '"';
        $newPasswordBytes = iconv('UTF-8', 'UTF-16LE', $newPassword);

        // Update the unicodePwd attribute
        $entry = [
            'unicodePwd' => $newPasswordBytes,
        ];
        try{
            ldap_mod_replace($ldapConn, $userDn, $entry);
        } catch (\Exception $e) {
            return false;
        }
    
        // Check if the modification was successful
        if (ldap_error($ldapConn) === 'Success') {
            $result = true;
        } else {
            $result = false;
        }

        // Close the LDAP connection
        ldap_close($ldapConn);

        return $result;
    }
    

///////////////////////////////////////////////////////////////////////



    function changeADUserPassword($email, $newPassword) {
        // LDAP connection parameters
        $ldapHost = env('AD_SERVER');
        $ldapUsername = env('AD_DAILYMAILUSER_USERNAME');
        $ldapPassword = env('AD_DAILYMAILUSER_PASSWORD');
    
        // LDAPRecord configuration
        $config = [
            'default' => [
                'hosts' => [$ldapHost],
                'username' => $ldapUsername,
                'password' => $ldapPassword,
                'base_dn' => env('LDAP_BASE_DN'),
                'use_ssl' => false, // Set to true if using LDAPS (LDAP over SSL)
        'use_tls' => true, // Set to true if using StartTLS (LDAP with TLS)
        'use_sasl' => false,
            ],
        ];
    
        // Establish LDAP connection
        Container::setDefaultConnection('default', $config);
    
        try {
            // Find the user by email
            $user = ADUser::where('mail', '=', $email)->first();
    
            if ($user) {
                // Set the new password
                $user->setPassword($newPassword, PASSWORD_ARGON2I);
    
                // Save the user (change the password)
                $user->save();
    
                return true;
            } else {
                echo "User with email '$email' not found.";
            }
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    
        return false;
    }
}