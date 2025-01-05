<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Requests\SelfUpdateEmployeeRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmployeeController extends Controller
{


    private function validateRole($employee)
    {
        // Mencegah yang berbeda company_id mengubah employee company_id lain
        if (Auth::user()->role !== 'superadmin' && Auth::user()->company_id !== $employee->company_id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses'
            ], 403));
        }
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with('company')->where('role', 'employee');

        /**
         * Jika role bukan superadmin maka hanya akan bisa melihat data dari perusahaan yang sama
         */
        if (Auth::user()->role !== 'superadmin') {
            $query->where('company_id', Auth::user()->company_id);
        }

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

        $manager = $query->paginate(20)->appends(request()->query());

        // Jika tidak ada data
        if ($manager->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $manager
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(StoreEmployeeRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {
            $password = $data['password'];
            $data['password'] = Hash::make($password);
            $data['role'] = 'employee';

            // Agar memastikan data konsisten, role selain superadmin hanya akan membuat karyawan dengan company_id yang sama meskipun menambahkannya di request input
            if (Auth::user()->role !== 'superadmin') {
                $data['company_id'] = Auth::user()->company_id;
            }
            $user = User::create($data);
            unset($user['password']);
            DB::commit();
            return response()->json([
                'success' => true,
                'data' => $user
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->responseErrors($th->getMessage());
        }
    }


    public function show()
    {
        $user = User::with('company')->findOrFail(Auth::user()->id);
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $employee = User::with('company')->findOrFail($id);

        // Validasi
        $this->validateRole($employee);
        return response()->json([
            'success' => true,
            'data' => $employee
        ]);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, User $employee)
    {

        // Validasi
        $this->validateRole($employee);

        $data = $request->validated();
        $employee->email = $data['email'];
        $employee->name = $data['name'];
        $employee->phone = $data['phone'];

        // Agar memastikan data konsisten, role selain superadmin hanya akan membuat karyawan dengan company_id yang sama meskipun menambahkannya di request input
        if (Auth::user()->role === 'superadmin') {
            $employee->company_id = $data['company_id'];
        }
        $employee->address = $data['address'] ?? null;

        if (!empty($data['password'])) {
            $password = Hash::make($data['password']);
            $employee->password = $password;
        }

        $employee->save();

        return response()->json([
            'success' => true,
            'data' => $employee
        ]);
    }


    public function selfUpdate(SelfUpdateEmployeeRequest $request)
    {
        $data = $request->validated();
        $manager = User::find(Auth::user()->id);
        $manager->name = $data['name'];
        $manager->phone = $data['phone'];
        $manager->address = $data['address'] ?? null;

        if (!empty($data['password'])) {
            $password = $data['password'];
            $manager->password = $password;
        }

        $manager->save();

        return response()->json([
            'success' => true,
            'data' => $manager
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $employee)
    {
        // Validasi
        $this->validateRole($employee);
        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menghapus data'
        ]);
    }
}
