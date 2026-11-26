<?php

declare(strict_types=1);

namespace Tests\Support\Licensing;

use App\Application\Account\ActivityRecorder;
use App\Application\Account\SignUp;
use App\Application\Licensing\AuthoriseToolUse;
use App\Application\Licensing\BuyPackage;
use App\Application\Licensing\GrantInitialLicence;
use App\Application\Licensing\PasswordConfirmation;
use App\Application\Licensing\PurchaseConfirmation;
use App\Application\Licensing\PurchaseInput;
use App\Application\Licensing\ToolAccess;
use App\Domain\Account\AccountId;
use App\Domain\Licensing\Licence;
use Tests\Support\Account\AccountKit;

/**
 * The adapters and services of one licensing test, sharing a clock with an account kit.
 */
final class LicensingKit
{
    public InMemoryPackageRepository $packages;

    public InMemoryLicenceRepository $licences;

    public InMemoryToolRepository $tools;

    public function __construct(public AccountKit $accounts = new AccountKit)
    {
        $this->tools = new InMemoryToolRepository;
        $this->packages = new InMemoryPackageRepository($this->tools);
        $this->licences = new InMemoryLicenceRepository;
    }

    /**
     * Signs Ana up (password "correct horse") with her initial licence.
     */
    public function signUp(): AccountId
    {
        return new SignUp(
            $this->accounts->register(),
            new GrantInitialLicence($this->packages, $this->licences, $this->accounts->clock),
        )->handle(AccountKit::input());
    }

    /**
     * Buys a package or custom tools for the account.
     *
     * @param  list<string>  $tools
     */
    public function buy(AccountId $buyer, ?int $package, int $days = 30, array $tools = [], string $password = 'correct horse'): Licence
    {
        return new BuyPackage(
            new PasswordConfirmation($this->accounts->accounts, $this->accounts->hasher),
            $this->packages,
            $this->licences,
            new ActivityRecorder($this->accounts->activity, $this->accounts->clock),
        )->handle(new PurchaseInput($buyer, $package, $tools, new PurchaseConfirmation($days, $password)));
    }

    /**
     * Whether the account may use the tool now.
     */
    public function access(AccountId $account, string $tool): ToolAccess
    {
        return new AuthoriseToolUse($this->licences, $this->tools, $this->accounts->clock)->handle($account, $tool);
    }
}
