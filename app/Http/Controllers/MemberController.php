<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberRequest;
use App\User;
use Auth;

class MemberController extends Controller
{
    /**
     * MemberController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth')->except([
            'show',
        ]);
    }

    /**
     * Edit profile.
     *
     * @return $this
     */
    public function edit()
    {
        $user = Auth::user();

        return view('members.manage.edit')->with(['user' => $user]);
    }

    /**
     * Update member.
     *
     * @param MemberRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(MemberRequest $request)
    {
        $user = Auth::user();

        $user->name = $request->name;
        $user->email = $request->email;
        if (!empty($request->password)) {
            $user->password = bcrypt($request->password);
        }
        $user->save();

        flash('Je profiel is bijgewertkt.', 'success');

        return redirect(route('user.edit'));
    }
}
