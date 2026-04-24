<?php
// ============================================================================
// footer.php — Cierra el HTML que abrio header.php
// Ubicacion: includes/footer.php
//
// PARA QUE SIRVE:
//   Cierra todos los tags HTML que header.php abrio:
//   article, main, div.page, body, html.
//   Cada pagina lo incluye al final: require_once '../includes/footer.php';
// ============================================================================
?>

            </article>
        </main>
    </div>

</body>
</html>

<?php
// ob_end_flush() = Output Buffering End Flush
//   "Termina de acumular y ENVIA TODO al navegador de una vez."
//
// Recordar: al inicio (header.php) hicimos ob_start() que empezo a acumular
// todo el HTML en memoria. Ahora ob_end_flush() envia todo ese HTML acumulado
// al navegador. Es como soltar el agua de una represa.
//
// Sin ob_start()/ob_end_flush(), header('Location:...') fallaria si ya se
// genero HTML antes (porque HTTP no permite enviar headers despues del body).
ob_end_flush();
?>
