<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Mail\ForgetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;

class CompanyController extends Controller
{


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Company::query();

        if (!empty($request->name)) {
            $query->where('name', 'LIKE', "%{$request->name}%");
        }

        // Sorting
        $sortBy = $request->input('sortBy', 'id'); // Default sorting berdasarkan ID
        $sortDir = $request->input('sortDir', 'asc'); // Default ascending

        if (in_array($sortBy, ['id', 'name', 'email', 'phone'])) { // Validasi kolom yang diizinkan
            if ($sortDir === 'asc') {
                $query->orderBy($sortBy);
            } else {
                $query->orderByDesc($sortBy);
            }
        }

        $companies = $query->paginate(20)->appends(request()->query());

        // Jika tidak ada data
        if ($companies->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $companies
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            Company::create($data);

            $response = [
                'email' => $data['email'],
                'name' => $data['name'],
                'phone' => $data['phone'],
            ];
            DB::commit();
            return response()->json([
                'success' => true,
                'data' => $response
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->responseErrors($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        return response()->json([
            'success' => true,
            'data' => $company
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $data = $request->validated();
        $company->email = $data['email'];
        $company->name = $data['name'];
        $company->phone = $data['phone'];
        $company->save();

        return response()->json([
            'success' => true,
            'data' => $company
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        $company->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menghapus data'
        ]);
    }
}
