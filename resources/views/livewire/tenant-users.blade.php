<div class="page-shell">
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">Tenancy e usuários</p>
            <h1 class="page-title mt-2">Equipe de {{ $tenant->name }}</h1>
            <p class="page-description">Convide usuários, altere papéis e controle o acesso sem afetar vínculos em outras estamparias.</p>
        </div>
        <a href="{{ route('tenant.dashboard') }}" class="button-secondary">Voltar ao dashboard</a>
    </div>

    @if(session('success'))
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-700">{{ session('warning') }}</div>
    @endif

    @if($inviteUrl)
        <div class="mt-6 rounded-2xl border border-brand-200 bg-brand-50 p-5">
            <p class="text-xs font-extrabold text-brand-800">Link do convite criado</p>
            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                <input value="{{ $inviteUrl }}" readonly class="field-input flex-1 font-mono text-xs">
                <button type="button" class="button-primary" onclick="navigator.clipboard.writeText(@js($inviteUrl))">Copiar link</button>
            </div>
            <p class="mt-2 text-[11px] text-brand-700/70">Em ambiente local, o envio e a URL são registrados em <code>storage/logs/laravel.log</code>.</p>
        </div>
    @endif

    <section class="mt-8 grid gap-6 xl:grid-cols-[.7fr_1.3fr]">
        <div class="surface-card p-5">
            <h2 class="text-sm font-extrabold">Convidar usuário</h2>
            <p class="mt-1 text-xs text-ink-400">O convite expira em 7 dias.</p>

            <form wire:submit="invite" class="mt-6 space-y-4">
                <div>
                    <label class="field-label" for="email">E-mail</label>
                    <input wire:model="email" id="email" type="email" class="field-input" placeholder="pessoa@empresa.com">
                    @error('email')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="field-label" for="role">Papel</label>
                    <select wire:model="role" id="role" class="field-input">
                        @foreach($roles as $availableRole)
                            <option value="{{ $availableRole->value }}">{{ $availableRole->label() }}</option>
                        @endforeach
                    </select>
                    @error('role')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <button class="button-primary w-full" wire:loading.attr="disabled">
                    <x-icon name="plus" class="size-4" />
                    Enviar convite
                </button>
            </form>

            <div class="mt-6 rounded-2xl bg-app p-4">
                <p class="text-xs font-bold text-ink-700">Proteções</p>
                <ul class="mt-3 space-y-2 text-[11px] leading-5 text-ink-400">
                    <li>• Um convite pendente por e-mail e tenant.</li>
                    <li>• Token armazenado somente em hash.</li>
                    <li>• Último Owner não pode ser removido.</li>
                    <li>• Alterações geram AuditLog.</li>
                </ul>
            </div>
        </div>

        <div class="table-shell overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Papel</th>
                        <th>Status</th>
                        <th>Entrada</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($memberships as $membership)
                        <tr wire:key="membership-{{ $membership->id }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-100 text-xs font-extrabold text-brand-700">{{ str($membership->user->name)->substr(0, 2)->upper() }}</span>
                                    <div>
                                        <p class="font-bold text-ink-800">{{ $membership->user->name }}</p>
                                        <p class="mt-0.5 text-[11px] text-ink-400">{{ $membership->user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <select
                                    class="rounded-lg border-line bg-white px-2 py-1.5 text-xs font-semibold"
                                    wire:change="changeRole('{{ $membership->id }}', $event.target.value)"
                                    @disabled((string) $membership->user_id === (string) auth()->id())
                                >
                                    @foreach($roles as $availableRole)
                                        <option value="{{ $availableRole->value }}" @selected($membership->role === $availableRole)>{{ $availableRole->label() }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <span @class([
                                    'status-badge',
                                    'status-success' => $membership->status->value === 'ACTIVE',
                                    'status-warning' => $membership->status->value === 'SUSPENDED',
                                    'status-neutral' => $membership->status->value === 'REVOKED',
                                ])>{{ $membership->status->label() }}</span>
                            </td>
                            <td class="text-xs text-ink-400">{{ $membership->joined_at?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    @if((string) $membership->user_id !== (string) auth()->id())
                                        <button wire:click="toggleStatus('{{ $membership->id }}')" class="button-ghost px-2 py-1.5 text-xs">
                                            {{ $membership->status->value === 'ACTIVE' ? 'Suspender' : 'Ativar' }}
                                        </button>
                                        @if($membership->status->value === 'ACTIVE' && $membership->role->value !== 'OWNER')
                                            <button
                                                wire:click="transferOwnership('{{ $membership->id }}')"
                                                wire:confirm="Transferir a propriedade para este usuário? Seu papel passará a Usuário."
                                                class="button-ghost px-2 py-1.5 text-xs text-brand-600"
                                            >Transferir</button>
                                        @endif
                                    @else
                                        <span class="text-[10px] font-semibold text-ink-300">Você</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-8 surface-card overflow-hidden">
        <div class="surface-card-header">
            <div>
                <h2 class="text-sm font-extrabold">Convites pendentes</h2>
                <p class="mt-1 text-xs text-ink-400">Convites ainda não aceitos.</p>
            </div>
            <span class="status-badge status-neutral">{{ $invitations->count() }}</span>
        </div>

        @forelse($invitations as $invitation)
            <div class="flex flex-col gap-4 border-b border-line px-5 py-4 last:border-b-0 sm:flex-row sm:items-center">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-ink-800">{{ $invitation->email }}</p>
                    <p class="mt-1 text-[11px] text-ink-400">{{ $invitation->role->label() }} • expira {{ $invitation->expires_at->diffForHumans() }}</p>
                </div>
                <button wire:click="revokeInvitation('{{ $invitation->id }}')" wire:confirm="Revogar este convite?" class="button-secondary px-3 py-2 text-xs">Revogar</button>
            </div>
        @empty
            <div class="px-5 py-10 text-center text-sm text-ink-400">Nenhum convite pendente.</div>
        @endforelse
    </section>
</div>
