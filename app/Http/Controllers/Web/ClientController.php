<?php

namespace App\Http\Controllers\Web;

use App\Actions\Client\CreateClient;
use App\Actions\Client\DeleteClient;
use App\Actions\Client\UpdateClient;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClientController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', Client::class);

        $clients = Client::orderBy('name')->paginate(15);

        return view('clients.index', compact('clients'));
    }

    public function create(): View
    {
        $this->authorize('create', Client::class);

        return view('clients.create');
    }

    public function store(StoreClientRequest $request, CreateClient $createClient): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $client = $createClient->handle($request->validated());

        return redirect()->route('clients.show', $client);
    }

    public function show(Client $client): View
    {
        $this->authorize('view', $client);

        return view('clients.show', compact('client'));
    }

    public function edit(Client $client): View
    {
        $this->authorize('update', $client);

        return view('clients.edit', compact('client'));
    }

    public function update(UpdateClientRequest $request, Client $client, UpdateClient $updateClient): RedirectResponse
    {
        $this->authorize('update', $client);

        $updateClient->handle($client, $request->validated());

        return redirect()->route('clients.show', $client);
    }

    public function destroy(Client $client, DeleteClient $deleteClient): RedirectResponse
    {
        $this->authorize('delete', $client);

        $deleteClient->handle($client);

        return redirect()->route('clients.index');
    }
}
