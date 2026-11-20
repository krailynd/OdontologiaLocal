<?php
/*
 * PARCIAL: info_pagina.php — caja «¿Qué hace esta pantalla?»
 * Variables que la vista debe definir ANTES de incluirla:
 *   $infoQueHace  — para qué sirve la pantalla
 *   $infoPeticion — qué petición llama (GET/POST y a dónde)
 *   $infoNoHace   — qué NO hace (límites honestos)
 *   $infoArchivo  — archivo(s) y método(s) que la implementan
 * Es documentación DENTRO de la propia página, para estudiar y
 * exponer el sistema sin abrir el código.
 */
?>
<details class="info-pagina">
    <summary>ℹ️ ¿Qué hace esta pantalla? (explicación técnica)</summary>
    <ul>
        <li><strong>Qué hace:</strong> <?= e($infoQueHace) ?></li>
        <li><strong>Qué petición llama:</strong> <?= e($infoPeticion) ?></li>
        <li><strong>Qué NO hace:</strong> <?= e($infoNoHace) ?></li>
        <li><strong>Dónde está en el código:</strong> <code><?= e($infoArchivo) ?></code></li>
    </ul>
</details>
