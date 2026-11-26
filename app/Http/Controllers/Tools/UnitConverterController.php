<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tools;

use App\Application\Licensing\RecordToolUse;
use App\Application\UnitConversion\ConversionResult;
use App\Application\UnitConversion\ConvertQuantity;
use App\Application\UnitConversion\ListConvertibleUnits;
use App\Domain\Quantity\IncompatibleUnit;
use App\Domain\Quantity\InvalidNumber;
use App\Domain\Quantity\UnknownUnit;
use App\Http\Controllers\Account\SignedInAccount;
use App\Http\Requests\Tools\ConvertUnitRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * The unit converter tool: the form, and the conversion as a page or as JSON. Converting needs a licence that
 * covers the converter (the `use-tool` gate on the route), and each conversion is recorded as a use.
 */
final readonly class UnitConverterController
{
    /**
     * The identifier of the converter among the tools.
     */
    public const string TOOL = 'unit-converter';

    public function __construct(
        private ListConvertibleUnits $units,
        private ConvertQuantity $convert,
        private RecordToolUse $recordUse,
    ) {}

    /**
     * The empty form.
     */
    public function show(): View
    {
        return $this->page(null);
    }

    /**
     * Converts the value; answers JSON to a client that asks for it, the page with the result otherwise.
     *
     * @throws ValidationException When the value is not a number or the units cannot be converted.
     * @throws AuthenticationException When nobody is signed in.
     */
    public function convert(ConvertUnitRequest $request): View|JsonResponse
    {
        $input = $request->toInput();

        try {
            $result = $this->convert->handle($input);
        } catch (InvalidNumber) {
            throw ValidationException::withMessages(['value' => __('unit-converter.errors.invalid_number')]);
        } catch (UnknownUnit $exception) {
            $field = $exception->id === $input->fromUnit ? 'from' : 'to';

            throw ValidationException::withMessages([$field => __('unit-converter.errors.unknown_unit')]);
        } catch (IncompatibleUnit) {
            throw ValidationException::withMessages(['to' => __('unit-converter.errors.incompatible_units')]);
        }

        $this->recordUse->handle(SignedInAccount::id($request), self::TOOL);

        if ($request->expectsJson()) {
            return new JsonResponse($result);
        }

        return $this->page($result);
    }

    /**
     * The converter page, with a result when there is one.
     */
    private function page(?ConversionResult $result): View
    {
        return view('tools.unit-converter', ['groups' => $this->units->handle(), 'result' => $result]);
    }
}
