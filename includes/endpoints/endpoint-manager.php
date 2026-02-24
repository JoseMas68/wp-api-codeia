<?php
/**
 * Endpoint Manager
 *
 * Gestiona los endpoints de la API.
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia;

/**
 * Class Endpoint_Manager
 *
 * @package WP_API_Codeia
 */
class Endpoint_Manager {

    /**
     * Inicializar el endpoint manager
     *
     * @return void
     */
    public function init(): void {
        // Hooks de endpoints
    }

    /**
     * Procesar una request
     *
     * @param API_Request $request Request a procesar
     * @return API_Response Respuesta generada
     */
    public function process_request(API_Request $request): API_Response {
        // TODO: Implementar procesamiento completo
        return new API_Response([
            'message' => 'Endpoint en desarrollo',
            'endpoint' => $request->get_endpoint(),
            'version' => $request->get_version(),
        ], 200);
    }
}

/**
 * Class API_Request
 *
 * @package WP_API_Codeia
 */
class API_Request {

    private string $version;
    private string $endpoint;
    private ?string $item_id;

    public function __construct(string $version, string $endpoint, ?string $item_id = null) {
        $this->version = $version;
        $this->endpoint = $endpoint;
        $this->item_id = $item_id;
    }

    public function get_version(): string {
        return $this->version;
    }

    public function get_endpoint(): string {
        return $this->endpoint;
    }

    public function get_item_id(): ?string {
        return $this->item_id;
    }
}

/**
 * Class API_Response
 *
 * @package WP_API_Codeia
 */
class API_Response {

    private $data;
    private int $status;
    private array $headers;

    public function __construct($data, int $status = 200, array $headers = []) {
        $this->data = $data;
        $this->status = $status;
        $this->headers = $headers;
    }

    public function send(): void {
        status_header($this->status);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        header('Content-Type: application/json');

        echo wp_json_encode($this->data);
        exit;
    }
}
