<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $doctors = User::query()
            ->where('role', 'doctor')
            ->with('healthcareProfessional.institution')
            ->orderBy('name')
            ->get();

        return DoctorResource::collection($doctors);
    }
}
