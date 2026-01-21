
function genererPostes() {
    const nb = document.getElementById("nbPostes").value;
    localStorage.setItem("nombrePostes", nb);
    const container = document.getElementById("postesContainer");

    container.innerHTML = "";

    for (let i = 1; i <= nb; i++) {
        const div = document.createElement("div");
        div.className = "poste";

        div.innerHTML = `
            <h2>Poste ${i}</h2>

            <label>Nombre d'étapes :</label>
            <input type="number" id="nbEtapes-${i}" class="nbEtapes" min="1">

            <button onclick="genererEtapes(${i})">Générer étapes</button>

            <div id="etapes-poste-${i}"></div>

            <h3>Pièces du poste :</h3>
            <div class="pieces" id="pieces-poste-${i}">
                <div class="piece">
                    <input type="text" placeholder="Nom pièce">
                    <input type="number" placeholder="Stock initial">
                </div>
            </div>

            <button onclick="ajouterPiece(${i})">+ Ajouter une pièce</button>
             <button type="button" onclick="genererCSVstock(${i})">📦 Générer CSV Stock</button>
                <button type="button" onclick="genererCSVetapes(${i})">🛠️ Générer CSV Étapes</button>
                
        `;

        container.appendChild(div);
    }
}



function ajouterPiece(numPoste) {
    const container = document.getElementById(`pieces-poste-${numPoste}`);

    const div = document.createElement("div");
    div.className = "piece";

    div.innerHTML = `
        <input type="text" placeholder="Nom pièce">
        <input type="number" placeholder="Stock initial">
    `;

    container.appendChild(div);
}




function ajouterPieceEtape(numPoste, numEtape) {
    const container = document.getElementById(`pieces-etape-${numPoste}-${numEtape}`);

    const div = document.createElement("div");
    div.className = "piece-etape";

    div.innerHTML = `
        <input type="text" placeholder="Nom pièce utilisée">
        <input type="number" placeholder="Quantité utilisée">
    `;

    container.appendChild(div);
}


function genererEtapes(numPoste) {
    const nb = document.querySelector(`#postesContainer .poste:nth-child(${numPoste}) .nbEtapes`).value;
    const container = document.getElementById(`etapes-poste-${numPoste}`);
    container.innerHTML = "";

    for (let i = 1; i <= nb; i++) {

        const div = document.createElement("div");
        div.className = "etape";

        div.innerHTML = `
            <h3>Étape ${i}</h3>

            <div class="pieces-etape" id="pieces-etape-${numPoste}-${i}">
                <div class="piece-etape">
                    <input type="text" placeholder="Nom pièce utilisée">
                    <input type="number" placeholder="Quantité utilisée">
                </div>
            </div>

            <button type="button" onclick="ajouterPieceEtape(${numPoste}, ${i})">+ Ajouter une pièce</button>
        `;

        container.appendChild(div);
    }
}



function genererCSVstock(numPoste) {
    const lignes = document.querySelectorAll(`#pieces-poste-${numPoste} .piece`);
    let csv = `"Nom","Stock"\n`;

    lignes.forEach(ligne => {
        const nom = ligne.querySelector('input[type="text"]').value.trim();
        const stock = ligne.querySelector('input[type="number"]').value;
        if (nom && stock) csv += `"${nom}","${stock}"\n`;
    });

    telechargerCSV(csv, `Poste${numPoste}_stock.csv`);
}



function genererCSVetapes(numPoste) {
    const etapes = document.querySelectorAll(`#etapes-poste-${numPoste} .etape`);
    let csv = "";

    etapes.forEach((etapeDiv, index) => {
        const numEtape = index + 1;
        let ligne = `"${numEtape}"`;

        const pieces = etapeDiv.querySelectorAll(".piece-etape");
        pieces.forEach(piece => {
            const nom = piece.querySelector('input[type="text"]').value.trim();
            const qte = piece.querySelector('input[type="number"]').value;
            if (nom && qte) ligne += `,"${nom}","${qte}"`;
        });

        csv += ligne + "\n";
    });

    telechargerCSV(csv, `Poste${numPoste}_etape.csv`);
}

function telechargerCSV(contenu, nomFichier) {
    const blob = new Blob([contenu], { type: "text/csv" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = nomFichier;
    a.click();
    URL.revokeObjectURL(url);
}


window.addEventListener("DOMContentLoaded", () => {

    const nbPostes = localStorage.getItem("nombrePostes") || 1;
    const select = document.getElementById("postes-select");

    // On vide le select
    select.innerHTML = "";

    // On génère automatiquement les options
    for (let i = 1; i <= nbPostes; i++) {
        const opt = document.createElement("option");
        opt.value = i;
        opt.textContent = "Poste " + i;
        select.appendChild(opt);
    }

    // On récupère soit l'URL, soit le dernier poste utilisé
    const params = new URLSearchParams(window.location.search);
    const fromUrl = params.get("poste");
    const fromStorage = localStorage.getItem("posteSelectionne");

    const posteActuel = fromUrl || fromStorage || 1;

    // On applique la valeur
    select.value = posteActuel;
    document.getElementById("poste-number").textContent = posteActuel;

    // On change de poste quand la liste change
    select.addEventListener("change", () => {
        localStorage.setItem("posteSelectionne", select.value);
        window.location.search = "?poste=" + select.value;
    });
});
