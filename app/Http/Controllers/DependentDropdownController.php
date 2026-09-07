<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Employee;
use Inertia\Inertia;

class DependentDropdownController extends Controller
{
    public function index()
    {
        $provinces = Province::orderBy('name')->get();
        return Inertia::render('StudyCases/DependentDropdown', [
            'provinces' => $provinces
        ]);
    }

    public function getCities(Request $request)
    {
        $cities = City::where('province_id', $request->province_id)->orderBy('name')->get();
        return response()->json($cities);
    }

    public function getDistricts(Request $request)
    {
        $districts = District::where('city_id', $request->city_id)->orderBy('name')->get();
        return response()->json($districts);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'district_id' => 'required|exists:districts,id',
        ]);

        Employee::create($request->only('name', 'province_id', 'city_id', 'district_id'));

        return redirect()->back()->with('success', 'Data employee berhasil disimpan!');
    }
}
