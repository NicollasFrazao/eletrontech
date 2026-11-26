<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tools;

use App\Application\Calculation\Calculate;
use App\Application\Calculation\CalculationInput;
use App\Application\Calculation\CalculationResult;
use App\Application\Calculation\DescribeCalculator;
use App\Application\Calculation\ListCalculators;
use App\Application\Licensing\RecordToolUse;
use App\Domain\Calculator\InvalidInput;
use App\Domain\Calculator\UnknownCalculator;
use App\Domain\Quantity\QuantityError;
use App\Http\Controllers\Account\SignedInAccount;
use App\Http\Requests\Tools\CalculateRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The calculators: the list by group, the form of each one, and a calculation, each as a page or as JSON.
 * Calculating needs a licence that covers the calculator (the `use-tool` gate on the route), and each one is
 * recorded as a use of the tool.
 */
final readonly class CalculatorController
{
    public function __construct(
        private ListCalculators $list,
        private DescribeCalculator $describe,
        private Calculate $calculate,
        private RecordToolUse $recordUse,
    ) {}

    /**
     * Every calculator with the schema of its inputs: JSON to a client that asks for it, the page by group otherwise.
     */
    public function index(Request $request): View|JsonResponse
    {
        $list = $this->list->handle();

        if ($request->expectsJson()) {
            return JsonResponse::fromJsonString(json_encode($list, JSON_THROW_ON_ERROR));
        }

        return view('tools.calculators.index', ['groups' => $list->byGroup()]);
    }

    /**
     * The empty form of one calculator.
     *
     * @throws NotFoundHttpException When no calculator has the identifier.
     */
    public function show(string $calculator): View
    {
        return $this->page(new CalculationInput($calculator, []), null);
    }

    /**
     * Runs one calculator; a refused value is a validation error on its field. Answers JSON to a client that asks
     * for it, the calculator's page with the result otherwise.
     *
     * @throws NotFoundHttpException When no calculator has the identifier.
     * @throws ValidationException When a value is missing, cannot be read or is outside the calculator's range.
     * @throws AuthenticationException When nobody is signed in.
     */
    public function calculate(string $calculator, CalculateRequest $request): View|JsonResponse
    {
        $input = $request->toInput($calculator);

        try {
            $result = $this->calculate->handle($input);
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

        if ($request->expectsJson()) {
            return JsonResponse::fromJsonString(json_encode($result, JSON_THROW_ON_ERROR));
        }

        return $this->page($input, $result);
    }

    /**
     * The calculator's page: its form filled with what was typed, and the result when there is one.
     *
     * @throws NotFoundHttpException When no calculator has the identifier.
     */
    private function page(CalculationInput $input, ?CalculationResult $result): View
    {
        try {
            $calculator = $this->describe->handle($input->calculator);
        } catch (UnknownCalculator $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        return view('tools.calculators.show', [
            'calculator' => $calculator,
            'values' => $input->values,
            'units' => $input->units,
            'result' => $result,
        ]);
    }
}
