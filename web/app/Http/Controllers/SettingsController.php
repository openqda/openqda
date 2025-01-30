<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSettingRequest;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $query = Setting::query();

        if ($request->has('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->has('model_id')) {
            $query->where('model_id', (string) $request->model_id);
        }

        $settings = $query->paginate();

        return response()->json([
            'data' => $settings->items(),
            'meta' => [
                'current_page' => $settings->currentPage(),
                'last_page' => $settings->lastPage(),
                'per_page' => $settings->perPage(),
                'total' => $settings->total(),
            ],
        ]);
    }

    public function store(StoreSettingRequest $request)
    {
        $validated = $request->validated();
        $validated['model_id'] = (string) $validated['model_id'];

        $setting = Setting::create($validated);

        return response()->json($setting, Response::HTTP_CREATED);
    }

    public function show(Setting $setting)
    {
        return response()->json($setting);
    }

    public function update(UpdateSettingRequest $request, Setting $setting)
    {
        $setting->update($request->validated());

        return response()->json($setting);
    }

    public function destroy(Setting $setting)
    {
        $this->authorize('delete', $setting);

        $setting->delete();

        return response()->noContent();
    }

    // Update specific value in the settings
    public function updateValue(Request $request, Setting $setting)
    {
        $request->validate([
            'group' => ['required', 'string'],
            'key' => ['required', 'string'],
            'value' => ['required'],
        ]);

        $values = $setting->values;
        $values[$request->group][$request->key] = $request->value;

        $setting->update(['values' => $values]);

        return response()->json($setting);
    }
}
