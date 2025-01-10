<?php

namespace App\DAO;

use \PDO;
use App\Modelo\Producto;

class ProductoDao {

    private $bd;

    public function __construct($bd) {
        $this->bd = $bd;
    }

    public function crea(Producto $producto): bool {
        
    }

    public function modifica(Producto $producto): bool {
        
    }

    public function elimina(string $productoId): bool {
        
    }

    /**
     * Recupera todos los objetos Producto
     * 
     * 
     * @returns array Producto con todos los productos almacenados
     */
    public function recuperaTodo(): array {
        $sql = "select * from productos order by nombre";
        $sth = $this->bd->prepare($sql);
        $sth->execute();
        $sth->setFetchMode(PDO::FETCH_CLASS, Producto::class);
        $productos = $sth->fetchAll();
        return $productos;
    }

}
