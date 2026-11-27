<?php

namespace App\Http\Livewire;

use App\Models\CaisseOperation;
use App\Models\RefTypePaiement;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentMethodManager extends Component
{
    use WithPagination;

    public $search = '';
    public $paymentMethodId;
    public $label = '';
    public $showModal = false;
    public $showDeleteModal = false;
    public $paymentMethodToDelete;

    public function boot(): void
    {
        abort_unless(Auth::check() && Auth::user()->isDocteurProprietaire(), 403);
    }

    protected function rules(): array
    {
        return [
            'label' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('ref_type_paiement', 'LibPaie')
                    ->ignore($this->paymentMethodId, 'idtypepaie'),
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'label.required' => 'Le libellé du mode de paiement est obligatoire.',
            'label.unique' => 'Ce mode de paiement existe déjà.',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openModal($id = null): void
    {
        $this->resetForm();

        if ($id) {
            $method = RefTypePaiement::findOrFail($id);
            $this->paymentMethodId = $method->idtypepaie;
            $this->label = $method->LibPaie;
        }

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate();
        $label = trim($this->label);

        if ($this->paymentMethodId) {
            RefTypePaiement::findOrFail($this->paymentMethodId)->update(['LibPaie' => $label]);
            session()->flash('message', 'Mode de paiement modifié avec succès.');
        } else {
            RefTypePaiement::create(['LibPaie' => $label]);
            session()->flash('message', 'Mode de paiement ajouté avec succès.');
        }

        $this->closeModal();
    }

    public function confirmDelete($id): void
    {
        $this->paymentMethodToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deletePaymentMethod(): void
    {
        $method = RefTypePaiement::find($this->paymentMethodToDelete);

        if (!$method) {
            $this->closeDeleteModal();
            return;
        }

        if (CaisseOperation::where('fkidtypePaie', $method->idtypepaie)->exists()) {
            session()->flash('error', 'Ce mode est déjà utilisé en caisse. Il ne peut pas être supprimé afin de préserver l’historique.');
            $this->closeDeleteModal();
            return;
        }

        try {
            $method->delete();
            session()->flash('message', 'Mode de paiement supprimé avec succès.');
        } catch (QueryException $exception) {
            session()->flash('error', 'Ce mode de paiement est déjà utilisé et ne peut pas être supprimé.');
        }

        $this->closeDeleteModal();
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->paymentMethodToDelete = null;
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->paymentMethodId = null;
        $this->label = '';
    }

    public function render()
    {
        $methods = RefTypePaiement::query()
            ->when(trim($this->search) !== '', fn ($query) => $query->where('LibPaie', 'like', '%' . trim($this->search) . '%'))
            ->orderBy('LibPaie')
            ->paginate(10);

        return view('livewire.payment-method-manager', compact('methods'));
    }
}
