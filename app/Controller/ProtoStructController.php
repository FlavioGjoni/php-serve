<?php

namespace App\Controller;

use Exception;
use Google\Protobuf\Struct;
use Google\Protobuf\Value;

class ProtoStructController {

    /**
     * @throws Exception
     */
    public function index(): void {
        $body_data_raw = file_get_contents('php://input');
        $body_data_raw = $body_data_raw === false ? '' : $body_data_raw;

        try {
            json_decode($body_data_raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $th) {
            http_response_code(400);
            echo json_encode(['error' => $th->getMessage(), 'message' => 'invalid json body']);
            exit();
        }

        $struct = new Struct();
        $struct->mergeFromJsonString($body_data_raw);

        echo json_encode($this->struct_to_raw_array($struct));
        exit();
    }

    protected function struct_to_raw_array(Struct $struct): array {
        $fields = [];

        foreach ($struct->getFields() as $key => $value) {
            /** @var Value $value */
            $fields[$key] = $this->value_to_raw($value);
        }

        return ['fields' => $fields];
    }

    protected function value_to_raw(Value $value): array {
        switch ($value->getKind()) {
            case 'number_value':
                return ['number_value' => $value->getNumberValue()];
            case 'string_value':
                return ['string_value' => $value->getStringValue()];
            case 'bool_value':
                return ['bool_value' => $value->getBoolValue()];
            case 'null_value':
                return ['null_value' => null];
            case 'struct_value':
                return ['struct_value' => $this->struct_to_raw_array($value->getStructValue())];
            case 'list_value':
                $items = [];
                foreach ($value->getListValue()->getValues() as $v) {
                    $items[] = $this->value_to_raw($v);
                }

                return ['list_value' => ['values' => $items]];
            default:
                return [];
        }
    }
}
