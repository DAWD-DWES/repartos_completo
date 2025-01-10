<?php

namespace App\Modelo;

class Producto {

    /**
     * 
     * @var int Identificador del producto
     */
    private int $id;

    /**
     * 
     * @var string Nombre del producto
     */
    private string $nombre;

    /**
     * 
     * @var string Nombre corto del producto
     */
    private string $nombreCorto;

    /**
     * 
     * @var float Precio del producto
     */
    private float $pvp;

    /**
     * 
     * @var string Familia del producto
     */
    private $familia;

    /**
     * 
     * @var string Descripción del producto
     */
    private string $descripcion;

    public function __construct(string $nombre = null, string $nombreCorto = null, string $descripcion = null, float $pvp = null, string $familia = null) {
        if (func_num_args() > 0) {
            $this->nombre = $nombre;
            $this->nombreCorto = $nombreCorto;
            $this->descripcion = $descripcion;
            $this->pvp = $pvp;
            $this->familia = $familia;
        }
    }

    public function getId(): string {
        return $this->id;
    }

    public function getNombre(): string {
        return $this->nombre;
    }

    public function getNombreCorto(): string {
        return $this->nombre_corto;
    }

    public function getPvp(): float {
        return $this->pvp;
    }

    public function getFamilia(): string {
        return $this->familia;
    }

    public function getDescripcion(): string {
        return $this->descripcion;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function setNombre(string $nombre): void {
        $this->nombre = $nombre;
    }

    public function setNombreCorto(string $nombreCorto): void {
        $this->nombreCorto = $nombreCorto;
    }

    public function setPvp(float $pvp): void {
        $this->pvp = $pvp;
    }

    public function setFamilia(string $familia): void {
        $this->familia = $familia;
    }

    public function setDescripcion(string $descripcion): void {
        $this->descripcion = $descripcion;
    }
}
