<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        if ($request->is('admin') || $request->is('admin/*')) {
            return redirect()->guest('/admin/login');
        }

        if ($request->is('teacher') || $request->is('teacher/*')) {
            return redirect('/teacher/login');
        }

        if ($request->is('student') || $request->is('student/*')) {
            return redirect()->guest('/student/login');
        }

        // return redirect()->guest(route('login'));
    }
}
