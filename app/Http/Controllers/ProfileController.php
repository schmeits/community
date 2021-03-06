<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Profile;
use Auth;
use Carbon\Carbon;
use Route;

class ProfileController extends Controller
{
    /**
     * ProfileController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth')->only([
            'myProfiles', 'create', 'store',
            'edit', 'update',
        ]);
    }

    /**
     * Get the index of all profiles.
     *
     * @return $this
     */
    public function index()
    {
        $view = $this->getTemplateByRoute();
        $profiles = $this->getProfilesByRoute();

        return view('profiles.'.$view)->with([
            'profiles' => $profiles,
        ]);
    }

    /**
     * Display a company profile.
     *
     * @param Profile $profile
     *
     * @return $this
     */
    public function show(Profile $profile)
    {
        return view('profiles.show')->with(['profile' => $profile]);
    }

    /**
     * Returns the (paginated) list of profiles based on the current route.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection|static[]
     */
    private function getProfilesByRoute()
    {
        switch (Route::currentRouteName()) {
            case 'guide':
                return Profile::filter(request()->all())->sorted()->paginateFilter(9);
            case 'guide.map':
                return Profile::filter(request()->all())->sorted()->get();
            case 'guide.list':
                return Profile::filter(request()->all())->sorted()->get();
        }
    }

    /**
     * Returns the template name based on the current route.
     *
     * @return string
     */
    private function getTemplateByRoute()
    {
        switch (Route::currentRouteName()) {
            case 'guide':
                return 'cards';
            case 'guide.map':
                return 'map';
            case 'guide.list':
                return 'table';
        }
    }

    /**
     * List the current users profiles.
     *
     * @return $this
     */
    public function myProfiles()
    {
        return view('profiles.table')->with([
            'profiles' => Auth::user()->profiles,
        ]);
    }

    /**
     * Create a profile.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $profile = new Profile();

        return view('profiles.manage.create')->with(['profile' => $profile]);
    }

    /**
     * Save a profile to the database.
     *
     * @param ProfileRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(ProfileRequest $request)
    {
        $profile = Auth::user()->profiles()->create(array_merge($request->getValidInput(), [
            'user_id'       => Auth::user()->id,
            'logo'          => $this->uploadLogo(),
            'founded_at'    => $request->founded_at ? Carbon::createFromFormat('Y', $request->founded_at) : null,
            'hourly_rate'   => $request->hourly_rate ? floatval(str_replace([' ', ','], ['', '.'], $request->hourly_rate)) : null,
        ]));

        if (Auth::user()->profiles()->count() === 1) {
            Auth::user()->profiles()->updateExistingPivot($profile->id, ['primary' => 1]);
        }

        flash('Het profiel toegevoegd.');

        return redirect(route('profile.list'));
    }

    /**
     * Edit an existing profile.
     *
     * @param Profile $profile
     *
     * @return $this
     */
    public function edit(Profile $profile)
    {
        if (!Auth::user()->profiles->contains($profile)) {
            flash('Je hebt geen toegang tot dit profiel', 'error');

            return redirect(route('profile.list'));
        }

        return view('profiles.manage.edit')->with(['profile' => $profile]);
    }

    /**
     * Update a profile.
     *
     * @param ProfileRequest $request
     * @param Profile        $profile
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(ProfileRequest $request, Profile $profile)
    {
        if (!Auth::user()->profiles->contains($profile)) {
            flash('Je hebt geen toegang tot dit profiel', 'error');

            return redirect(route('profile.list'));
        }

        $profile->update(array_merge($request->getValidInput(), [
            'logo'          => $this->uploadLogo($profile->logo),
            'founded_at'    => $request->founded_at ? Carbon::createFromFormat('Y', $request->founded_at) : null,
            'hourly_rate'   => $request->hourly_rate ? floatval(str_replace([' ', ','], ['', '.'], $request->hourly_rate)) : null,
        ]));

        flash('Het bedrijfsprofiel is bijgewerkt', 'success');

        return redirect(route('profile.list'));
    }

    /**
     * Delete the logo.
     *
     * @param Profile $profile
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function removeLogo(Profile $profile)
    {
        if (file_exists(storage_path('app/public/uploads/logos/'.$profile->logo)) && $profile->logo != '') {
            File::delete(storage_path('app/public/uploads/articles/'.$profile->logo));
            $profile->logo = null;
            $profile->save();

            flash('Het logo is verwijderd.');
        }

        return redirect(route('profile.edit', [$profile->slug]));
    }

    /**
     * Upload a logo.
     *
     * @param string $old
     *
     * @return string
     */
    public function uploadLogo($old = null)
    {
        if (\Request::hasFile('logo')) {
            $image = \Request::file('logo');
            $filename = time().str_random(10).'.'.$image->getClientOriginalExtension();
            $path = storage_path('app/public/uploads/logos/'.$filename);

            try {
                \Image::make($image->getRealPath())->resize(400, 400)->save($path);

                if ($old != '') {
                    if (file_exists(storage_path('app/public/uploads/logos/'.$old))) {
                        \File::delete(storage_path('app/public/uploads/logos/'.$old));
                    }
                }

                return $filename;
            } catch (Exception $e) {
                return $old;
            }
        }

        return $old;
    }

    /**
     * Delete a profile.
     *
     * @param Profile $profile
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Profile $profile)
    {
        if (!Auth::user()->profiles->contains($profile)) {
            flash('Je hebt geen toegang tot dit profiel', 'error');

            return redirect(route('profile.list'));
        }

        $profile->delete();

        flash('Het bedrijfsprofiel is verwijderd.');

        return redirect(route('profile.list'));
    }
}
