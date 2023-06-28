<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use LdapRecord\Container;
use LdapRecord\Connection;
use LdapRecord\Models\ActiveDirectory\User as ADUser;

class AdApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $email = $request->input('email');

        if ($this->userAvailable($email)) {
            //reset the password of that user in Active Directory
            try{
                $this->resetPassword($email);
            }catch(Exception){
                //do something if error

            }
        } else {
            return $next($request);
        }

       
    }

    private function userAvailable($email)
    {
        $connection = new Connection([
                            'hosts'    => ['192.168.1.1'],
                            'username' => 'cn=user,dc=local,dc=com',
                            'password' => 'secret',
        ]);
        $connection->connect();
        Container::addConnection($connection, 'connection');

        // Search for the user by email
        $user = ADUser::on('connection')
            ->where('mail', '=', $email)
            ->first();

        return $user !== null;
    }

    private function resetPassword($email)
    {
        
    }
}
