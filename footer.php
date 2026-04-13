<style>
.pdf-footer {
    background-color: #f8f9fa;
    text-align: center;
    font-size: 12px;
    color: #495057;
    border-top: 1px solid #dee2e6;
    padding: 15px 10px;
    margin-top: 50px;
    line-height: 1.6;
}

.pdf-footer strong {
    color: #212529;
}
</style>

<div class="pdf-footer">
    <div>
        <strong>MEDICOUD</strong> – Service de Santé Universitaire
        | Campus Universitaire | Dakar, Sénégal
        | Tél : +221 78 441 34 00
        | Email : contact@coudmedical.sn
    </div>

    <div>
        Document confidentiel – Toute diffusion non autorisée est interdite.
    </div>

    <div>
        © <?= date('Y') ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript">
var theTime;

// la fonction ne commence � marcher qu'apres le premier clic

currentTime = new Date();
theTime = currentTime.getTime();
/////////////////////////////////////////////////

document.onmousemove = stockTime;
document.onkeydown = stockTime; //  pour prendre en compte les actions du clavier

function stockTime() {
    currentTime = new Date();
    theTime = currentTime.getTime();
}

function verifTime() {
    currentTime = new Date();
    var timeNow = currentTime.getTime();
    if (timeNow - theTime > 300000) {

        alert('Votre session a expir�. Veuillez vous reconnecter!');
        top.location.href = "index"
        top.location.href = "http://localhost/medical01/"

    }
}
window.setInterval("verifTime()", 300000);
</script>
</body>

</html>