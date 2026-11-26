<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tools;

use App\Application\Calculation\Calculate;
use App\Application\Calculation\ListCalculators;
use App\Application\Licensing\RecordToolUse;
use App\Domain\Calculator\InvalidInput;
use App\Domain\Calculator\UnknownCalculator;
use App\Domain\Quantity\QuantityError;
use App\Http\Controllers\Account\SignedInAccount;
use App\Http\Requests\Tools\CalculateRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The calculators as JSON: their input schemas, and a calculation with any of them. Calculating needs a licence
 * that covers the calculator (the `use-tool` gate on the route), and each one is recorded as a use of the tool.
 * The pages that use it come with the interface (E9).
 */
final readonly class CalculatorController
{
    public function __construct(
        private ListCalculators $list,
        private Calculate $calculate,
        private RecordToolUse $recordUse,
    ) {}

    /**
     * Every calculator with the schema of its inputs.
     */
    public function index(): JsonResponse
    {
        return JsonResponse::fromJsonString(json_encode($this->list->handle(), JSON_THROW_ON_ERROR));
    }

    /**
     * Runs one calculator; a refused value is a validation error on its field.
     *
     * @throws NotFoundHttpException When no calculator has the identifier.
     * @throws ValidationException When a value is missing, cannot be read or is outside the calculator's range.
     * @throws AuthenticationException When nobody is signed in.
     */
    public function calculate(string $calculator, CalculateRequest $request): JsonResponse
    {
        try {
            $result = $this->calculate->handle($request->toInput($calculator));
        } catch (UnknownCalculator $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        } catch (InvalidInput $exception) {
            throw ValidationException::withMessages([
                $exception->field => __('calculators.errors.'.$exception->problem->value),
            ]);
        } catch (QuantityError) {
            throw ValidationException::withMessages([
                InvalidInput::ALL_INPUTS => __('calculators.errors.impossible'),
            ]);
        }

        $this->recordUse->handle(SignedInAccount::id($request), $calculator);

        return JsonResponse::fromJsonString(json_encode($result, JSON_THROW_ON_ERROR));
    }
}
