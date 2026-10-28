<?php

namespace Database\Seeders;

use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Popula 10 clientes (contratantes) com bio e endereço, a partir de
 * database/seeders/data/clients.json.
 *
 * Os perfis variam em tipo de contratação (freelance pontual, horas recorrentes, projeto sazonal)
 * e em score, para dar variedade à listagem de clientes.
 */
class ClientProfilesSeeder extends Seeder
{
    private const PASSWORD = 'user1234!';

    public function run(): void
    {
        $clients = $this->loadClients();

        if (!$clients) {
            return;
        }

        foreach ($clients as $index => $data) {
            DB::transaction(fn () => $this->seedClient($data, $index));
        }
    }

    private function loadClients(): ?array
    {
        $path = database_path('seeders/data/clients.json');

        if (!file_exists($path)) {
            $this->command->warn("Arquivo não encontrado: {$path}");
            return null;
        }

        $clients = json_decode(file_get_contents($path), true);

        if (!is_array($clients) || $clients === []) {
            $this->command->warn('clients.json inválido ou vazio.');
            return null;
        }

        return $clients;
    }

    private function seedClient(array $data, int $index): ClientProfile
    {
        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ]
        );

        if (!$user->hasRole('client')) {
            $user->assignRole('client');
        }

        $clientProfile = ClientProfile::updateOrCreate(
            ['user_id' => $user->id],
            // O offset evita repetir os mesmos CPFs gerados pelo DevProfilesSeeder.
            [...$data['profile'], 'cpf' => $this->fakeCpf($index + 100)]
        );

        $this->seedAddress($clientProfile, $data['address']);

        return $clientProfile;
    }

    private function seedAddress(ClientProfile $clientProfile, array $address): void
    {
        // Criado pela relação morph para que addressable_id e addressable_type sejam preenchidos,
        // já que não estão no fillable do Address.
        $clientProfile->address()->forceDelete();
        $clientProfile->address()->create($address);
    }

    /**
     * CPF sintético com dígitos verificadores válidos, para não repetir o mesmo número em todos os clientes.
     */
    private function fakeCpf(int $seed): string
    {
        $digits = str_split(str_pad((string) (100000000 + ($seed * 7919) % 800000000), 9, '0', STR_PAD_LEFT));

        for ($position = 9; $position < 11; $position++) {
            $sum = 0;

            foreach (array_slice($digits, 0, $position) as $offset => $digit) {
                $sum += ((int) $digit) * (($position + 1) - $offset);
            }

            $digits[$position] = (string) (((10 * $sum) % 11) % 10);
        }

        return implode('', $digits);
    }
}
