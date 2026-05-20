<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebsiteRequest;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class WebsiteController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Welcome', [
            'sites' => Website::orderBy('sort_order')->orderBy('id')->get(),
            'canRegister' => Features::enabled(Features::registration()),
        ]);
    }

    public function index(): Response
    {
        return Inertia::render('sites/Index', [
            'sites' => Website::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('sites/Form', [
            'site' => null,
        ]);
    }

    public function store(WebsiteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('websites', 'public');
        }

        unset($data['image']);

        Website::create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Site added.')]);

        return to_route('sites.index');
    }

    public function edit(Website $site): Response
    {
        return Inertia::render('sites/Form', [
            'site' => $site,
        ]);
    }

    public function update(WebsiteRequest $request, Website $site): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($site->image_path) {
                Storage::disk('public')->delete($site->image_path);
            }

            $data['image_path'] = $request->file('image')->store('websites', 'public');
        }

        unset($data['image']);

        $site->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Site updated.')]);

        return to_route('sites.index');
    }

    public function destroy(Website $site): RedirectResponse
    {
        if ($site->image_path) {
            Storage::disk('public')->delete($site->image_path);
        }

        $site->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Site removed.')]);

        return to_route('sites.index');
    }
}
