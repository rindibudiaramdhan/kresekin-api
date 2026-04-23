<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AgentTenantRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentTenantController extends Controller
{
    public function index(Request $request): View
    {
        $tenants = Tenant::query()
            ->with('owner')
            ->latest()
            ->paginate(10);

        return view('agent.tenants.index', [
            'tenants' => $tenants,
        ]);
    }

    public function create(): View
    {
        return view('agent.tenants.form', [
            'tenant' => new Tenant(),
            'sellers' => User::query()->where('role', User::ROLE_SELLER)->orderBy('name')->get(),
            'categories' => Tenant::CATEGORIES,
        ]);
    }

    public function store(AgentTenantRequest $request): RedirectResponse
    {
        Tenant::query()->create([
            ...$request->validated(),
            'rating' => $request->validated()['rating'] ?? 0,
        ]);

        return redirect()
            ->route('agent.tenants.index')
            ->with('status', 'Tenant berhasil dibuat.');
    }

    public function edit(int $id): View
    {
        $tenant = Tenant::query()->findOrFail($id);

        return view('agent.tenants.form', [
            'tenant' => $tenant,
            'sellers' => User::query()->where('role', User::ROLE_SELLER)->orderBy('name')->get(),
            'categories' => Tenant::CATEGORIES,
        ]);
    }

    public function update(AgentTenantRequest $request, int $id): RedirectResponse
    {
        $tenant = Tenant::query()->findOrFail($id);
        $tenant->update([
            ...$request->validated(),
            'rating' => $request->validated()['rating'] ?? 0,
        ]);

        return redirect()
            ->route('agent.tenants.index')
            ->with('status', 'Tenant berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        Tenant::query()->findOrFail($id)->delete();

        return redirect()
            ->route('agent.tenants.index')
            ->with('status', 'Tenant berhasil dihapus.');
    }
}
