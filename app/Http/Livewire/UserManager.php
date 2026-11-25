<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\TUser;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

class UserManager extends Component
{
    use WithPagination;

    // Propriétés pour le formulaire
    public $userId;
    public $login;
    public $password;
    public $nomComplet;
    public $role;
    public $medecinId;
    public $isActive = true;
    public $isMasquer = false;

    // Propriétés pour la recherche et le tri
    public $search = '';
    public $sortField = 'NomComplet';
    public $sortDirection = 'asc';

    // Propriétés pour la modal
    public $showModal = false;
    public $showDeleteModal = false;
    public $userToDelete = null;
    public $modalTitle = '';

    protected $rules = [
        'login' => 'required|min:3|unique:t_user,login',
        'password' => 'required|min:6',
        'nomComplet' => 'required|min:3',
        'role' => 'required|in:1,2,3', // 1=Secrétaire, 2=Médecin, 3=Propriétaire
    ];

    protected $messages = [
        'login.required' => 'L\'identifiant est obligatoire.',
        'login.min' => 'L\'identifiant doit contenir au moins 3 caractères.',
        'login.unique' => 'Cet identifiant est déjà utilisé.',
        'password.required' => 'Le mot de passe est obligatoire.',
        'password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
        'nomComplet.required' => 'Le nom complet est obligatoire.',
        'nomComplet.min' => 'Le nom complet doit contenir au moins 3 caractères.',
        'role.required' => 'Le rôle est obligatoire.',
        'role.in' => 'Le rôle sélectionné est invalide.',
    ];

    public function render()
    {
        $cabinetId = Auth::user()->fkidcabinet;
        $users = TUser::where('fkidcabinet', $cabinetId)
        ->where(function($query) {
            $query->where('NomComplet', 'like', '%' . $this->search . '%')
                  ->orWhere('login', 'like', '%' . $this->search . '%');
        })
        ->where('ismasquer', false)
        ->orderBy($this->sortField, $this->sortDirection)
        ->paginate(10);

        return view('livewire.user-manager', [
            'users' => $users,
            'medecins' => \App\Models\Medecin::where('fkidcabinet', $cabinetId)
                ->orderBy('Nom')
                ->get(['idMedecin', 'Nom']),
            'activeOwnerCount' => TUser::where('fkidcabinet', $cabinetId)
                ->where('IdClasseUser', 3)
                ->where('ismasquer', false)
                ->count(),
        ]);
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openModal($mode = 'create', $userId = null)
    {
        $this->resetValidation();
        $this->reset(['userId', 'login', 'password', 'nomComplet', 'role', 'medecinId', 'isActive', 'isMasquer']);

        if ($mode === 'edit' && $userId) {
            $user = TUser::where('fkidcabinet', Auth::user()->fkidcabinet)->find($userId);
            if ($user) {
                $this->userId = $user->Iduser;
                $this->login = $user->login;
                $this->nomComplet = $user->NomComplet;
                $this->role = $user->IdClasseUser;
                $this->medecinId = $user->fkidmedecin;
                $this->isActive = !$user->ismasquer;
                $this->isMasquer = $user->ismasquer;
                $this->modalTitle = 'Modifier l\'utilisateur';
            }
        } else {
            $this->modalTitle = 'Créer un nouvel utilisateur';
        }

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetValidation();
        $this->reset(['userId', 'login', 'password', 'nomComplet', 'role', 'medecinId', 'isActive', 'isMasquer']);
    }

    public function save()
    {
        $rules = $this->rules;
        if ($this->userId) {
            $rules['login'] = 'required|min:3|unique:t_user,login,' . $this->userId . ',Iduser';
            $rules['password'] = 'nullable|min:6';
        }

        if (in_array((int) $this->role, [2, 3], true)) {
            $rules['medecinId'] = [
                'required',
                Rule::exists('medecins', 'idMedecin')
                    ->where(fn ($query) => $query->where('fkidcabinet', Auth::user()->fkidcabinet)),
            ];
        }
        $this->validate($rules);

        try {
            $fkidmedecin = in_array((int) $this->role, [2, 3], true)
                ? (int) $this->medecinId
                : 1;

            $userData = [
                'login' => $this->login,
                'NomComplet' => $this->nomComplet,
                'IdClasseUser' => $this->role,
                'ismasquer' => !$this->isActive,
                'fonction' => $this->role == 1 ? 'Secrétaire' : ($this->role == 2 ? 'Médecin' : 'Propriétaire'),
                'fkidmedecin' => $fkidmedecin,
                'fkidcabinet' => Auth::user()->fkidcabinet,
                'DtCr' => now(),
            ];

            if ($this->userId) {
                // Si c'est une modification
                $user = TUser::where('fkidcabinet', Auth::user()->fkidcabinet)
                    ->find($this->userId);
                if (!$user) {
                    throw new \Exception('Utilisateur non trouvé');
                }

                // Mettre à jour les données de base
                $user->update($userData);

                // Mettre à jour le mot de passe si fourni
                if (!empty($this->password)) {
                    $user->password = $this->password;
                    $user->save();
                }

                session()->flash('message', 'Utilisateur modifié avec succès.');
            } else {
                // Si c'est une création
                if (empty($this->password)) {
                    throw new \Exception('Le mot de passe est requis pour la création d\'un utilisateur');
                }
                $userData['password'] = $this->password;
                TUser::create($userData);
                session()->flash('message', 'Utilisateur créé avec succès.');
            }

            $this->closeModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Une erreur est survenue lors de l\'enregistrement de l\'utilisateur : ' . $e->getMessage());
        }
    }

    public function delete($userId)
    {
        try {
            $user = TUser::where('fkidcabinet', Auth::user()->fkidcabinet)->find($userId);
            if ($user) {
                // Vérifier si l'utilisateur n'est pas le dernier propriétaire
                if ($user->IdClasseUser == 3) {
                    $ownerCount = TUser::where('IdClasseUser', 3)
                        ->where('fkidcabinet', Auth::user()->fkidcabinet)
                        ->where('ismasquer', false)
                        ->count();
                    
                    if ($ownerCount <= 1) {
                        session()->flash('error', 'Impossible de supprimer le dernier propriétaire.');
                        return;
                    }
                }

                $user->ismasquer = true;
                $user->save();
                session()->flash('message', 'Utilisateur supprimé avec succès.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Une erreur est survenue lors de la suppression.');
        }
    }

    public function confirmDelete($userId)
    {
        $user = TUser::where('fkidcabinet', Auth::user()->fkidcabinet)->find($userId);
        if (!$user) {
            return;
        }

        $this->userToDelete = $user->Iduser;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed()
    {
        if ($this->userToDelete) {
            $this->delete($this->userToDelete);
        }

        $this->showDeleteModal = false;
        $this->userToDelete = null;
    }

    public function toggleStatus($userId)
    {
        try {
            $user = TUser::where('fkidcabinet', Auth::user()->fkidcabinet)->find($userId);
            if ($user) {
                // Vérifier si l'utilisateur n'est pas le dernier propriétaire actif
                if ($user->IdClasseUser == 3 && !$user->ismasquer) {
                    $activeOwnerCount = TUser::where('IdClasseUser', 3)
                        ->where('fkidcabinet', Auth::user()->fkidcabinet)
                        ->where('ismasquer', false)
                        ->count();
                    
                    if ($activeOwnerCount <= 1) {
                        session()->flash('error', 'Impossible de désactiver le dernier propriétaire actif.');
                        return;
                    }
                }

                $user->ismasquer = !$user->ismasquer;
                $user->save();
                session()->flash('message', 'Statut de l\'utilisateur modifié avec succès.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Une erreur est survenue lors de la modification du statut.');
        }
    }
}
