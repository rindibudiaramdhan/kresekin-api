<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AgentTenantRequest;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentTenantController extends Controller
{
    public function index(Request $request): View
    {
        $selectedSeller = $request->filled('seller') ? (int) $request->query('seller') : null;
        $selectedCategory = $request->filled('category') ? (string) $request->query('category') : null;
        $agentId = $request->user()->id;

        $query = Tenant::query()
            ->with('owner')
            ->where('agent_user_id', $agentId)
            ->when($selectedSeller, fn ($query) => $query->where('owner_user_id', $selectedSeller))
            ->when($selectedCategory, fn ($query) => $query->where('category', $selectedCategory))
            ->latest();

        $tenants = $query->paginate(10)->withQueryString();

        return view('agent.tenants.index', [
            'tenants' => $tenants,
            'sellers' => User::query()->where('role', User::ROLE_SELLER)->orderBy('name')->get(),
            'categories' => Tenant::CATEGORIES,
            'selectedSeller' => $selectedSeller,
            'selectedCategory' => $selectedCategory,
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
            'agent_user_id' => $request->user()->id,
            'rating' => $request->validated()['rating'] ?? 0,
        ]);

        return redirect()
            ->route('agent.tenants.index')
            ->with('status', 'Tenant berhasil dibuat.');
    }

    public function edit(int $id): View
    {
        $tenant = Tenant::query()
            ->where('agent_user_id', auth()->id())
            ->findOrFail($id);

        return view('agent.tenants.form', [
            'tenant' => $tenant,
            'sellers' => User::query()->where('role', User::ROLE_SELLER)->orderBy('name')->get(),
            'categories' => Tenant::CATEGORIES,
        ]);
    }

    public function show(int $id): View
    {
        $tenant = Tenant::query()
            ->with(['owner', 'products'])
            ->where('agent_user_id', auth()->id())
            ->findOrFail($id);

        return view('agent.tenants.show', [
            'tenant' => $tenant,
            'products' => $tenant->products()->latest()->limit(10)->get(),
        ]);
    }

    public function update(AgentTenantRequest $request, int $id): RedirectResponse
    {
        $tenant = Tenant::query()
            ->where('agent_user_id', $request->user()->id)
            ->findOrFail($id);
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
        Tenant::query()
            ->where('agent_user_id', auth()->id())
            ->findOrFail($id)
            ->delete();

        return redirect()
            ->route('agent.tenants.index')
            ->with('status', 'Tenant berhasil dihapus.');
    }
}
