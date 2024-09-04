<?php

namespace App\Http\Controllers;

use App\Model\Groups;
use App\Model\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->middleware('permission:edit user');

        return view('user.index', [
            'users' => User::with('permissions')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $this->middleware('permission:edit user');

        return view('user.show', [
            'user' => $user,
            'permissions' => Permission::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $this->middleware('permission:edit user');
        $user->fill($request->all());

        if (auth()->user()->can('edit permission')) {
            $permissions = $request->input('permissions');
            $user->syncPermissions($permissions);
        }

        if ($user->save()) {
            return redirect()->back()->with([
                'type'   => 'success',
                'Meldung'    => 'Daten gespeichert.',
            ]);
        }

        return redirect()->back()->with([
            'type'   => 'danger',
            'Meldung'    => 'Update fehlgeschlagen',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }

    public function sendVerification($user_id)
    {
        $user = User::find($user_id);
        $user->sendEmailVerificationNotification();

        return redirect()->back()->with([
            'type'   => 'success',
            'Meldung'    => 'E-Mail wurde versandt.',
        ]);
    }
}
