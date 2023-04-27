<template>
    <div class="game-wrapper">
        <table class="game-content">
            <thead v-if="json">
                <tr>
                    <th>
                        <div>
                            <img :src="json.you.icon == 'x' ? this.imgX : this.imgO" />
                            <h2 title="You" alt="You">You {{ json.you.id }}</h2>
                        </div>
                    </th>
                    <th class="rematch" v-on:click="typeof (json.turn) == 'string' && emitRematch()">
                        <div>
                            <template v-if="json.turn == json.you.id">
                                <!-- SEU TURNO -->
                                <img v-if="json.you.icon == 'x'" :src="imgXGray" />
                                <img v-if="json.you.icon == 'o'" :src="imgOGray" />
                                <h2>Turn</h2>
                            </template>
                            <template v-else-if="json.turn == json.opponent.id">
                                <!-- TURNO DO OPONENTE -->
                                <img v-if="json.opponent.icon == 'x'" :src="imgXGray" />
                                <img v-if="json.opponent.icon == 'o'" :src="imgOGray" />
                                <h2>Turn</h2>
                            </template>
                            <template v-else-if="json.turn.includes('victories')">
                                <!-- FIM DA PARTIDA COM UM GANHADOR -->
                                <img :src="imgRestart" />
                                <h2 v-if="json.turn.split('-')[1] == json.you.id">Victory</h2>
                                <h2 v-else>Defeat</h2>
                            </template>
                            <template v-else-if="json.turn.includes('ties')">
                                <!-- FIM DA PARTIDA POR EMPATE -->
                                <img :src="imgRestart" />
                                <h2>Tie</h2>
                            </template>
                        </div>
                    </th>
                    <th>
                        <div>
                            <img :src="json.opponent.icon == 'x' ? this.imgX : this.imgO" />
                            <h2 title="Opponent" alt="Opponent">Oppon... {{ json.opponent.id }}</h2>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row of numberRows">
                    <template v-for="column of numberColumns">
                        <td v-on:click="json && emitChoice(row + column)">
                            <img :src="cells[row + column]" />
                        </td>
                    </template>
                </tr>
            </tbody>
            <tfoot v-if="json">
                <tr>
                    <td class="victories"
                        :style="json.you.icon == 'x' ? 'background-color:var(--blue);' : 'background-color:var(--yellow);'">
                        <span v-if="json.you.icon == 'x'">x</span>
                        <span v-if="json.you.icon == 'o'">o</span>
                        <span>(you)</span>
                        <h2>{{ json.you.victories }}</h2>
                    </td>
                    <td class="ties">
                        <p>Ties</p>
                        <h2>{{ json.ties }}</h2>
                    </td>
                    <td class="victories"
                        :style="json.opponent.icon == 'x' ? 'background-color:var(--blue);' : 'background-color:var(--yellow);'">
                        <span v-if="json.opponent.icon == 'x'">x</span>
                        <span v-if="json.opponent.icon == 'o'">o</span>
                        <span>(opponent)</span>
                        <h2>{{ json.opponent.victories }}</h2>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</template>

<script>
import imgEmpty from "../assets/empty.svg"
import imgX from "../assets/icon-x.svg"
import imgO from "../assets/icon-o.svg"
import imgXGray from "../assets/icon-x-gray.svg"
import imgOGray from "../assets/icon-o-gray.svg"
import imgRestart from "../assets/icon-restart.svg"

export default {
    name: "GameWrapper",
    props: {
        json: null,
    },
    data() {
        return {
            numberRows: ["A", "B", "C"],
            numberColumns: [0, 1, 2],
            cells: {},
            imgEmpty: imgEmpty,
            imgX: imgX,
            imgO: imgO,
            imgXGray: imgXGray,
            imgOGray: imgOGray,
            imgRestart: imgRestart
        }
    },
    methods: {
        emitChoice(rowAndColumn) {
            //VERIFICA SE É O SEU TURNO
            if (this.json.turn == this.json.you.id) {

                //VERIFICA SE A CÉLULA ESTÁ VAZIA
                if (this.cells[rowAndColumn].includes("empty")) {
                    this.json.you.choices.push(rowAndColumn)
                    this.$emit("emitChoice", this.json);

                    //MUDANDO AS IMAGENS DAS CÉLULAS PELO CLICK DO YOU
                    this.json.you.choices.forEach((rowAndColumn) => {
                        switch (this.json.you.icon) {
                            case "x":
                                this.cells[rowAndColumn] = this.imgX
                                break;
                            case "o":
                                this.cells[rowAndColumn] = this.imgO
                                break;
                        }
                    })
                }

            }
        },
        emitRematch() {
            this.$emit("emitRematch");
            this.resetcells();
        },
        resetcells() {
            //ADICIONA IMAGENS VAZIAS AS CÉLULAS QUANDO O ELEMENTO É CRIADO
            this.numberRows.forEach((row) => {
                this.numberColumns.forEach((column) => {
                    this.cells[row + column] = this.imgEmpty;
                })
            })
        }
    },
    watch: {
        json(newJson) {
            if (this.json == null || this.json.type == "rematch-started") {
                //RESETANDO AS CÉLULAS
                this.resetcells();
            } else {
                //MUDANDO AS IMAGENS DAS CÉLULAS PELO CLICK DO OPPONENT
                newJson.opponent.choices.forEach((rowAndColumn) => {
                    switch (newJson.opponent.icon) {
                        case "x":
                            this.cells[rowAndColumn] = imgX
                            break;
                        case "o":
                            this.cells[rowAndColumn] = imgO
                            break;
                    }
                })
            }

        }
    },
    mounted() {
        this.resetcells();
    }
}
</script>

<style>
.game-wrapper {
    width: 55%;
}

table {
    width: 100%;
    border-spacing: 16px;
}

tbody td {
    position: relative;
    background-color: var(--second-bg);
    border-radius: 8px;
    padding: 28px 0;
    text-align: center;
    cursor: pointer;
    border-top: 0;
    border-right: 0;
    border-left: 0;
    border-bottom: 5px solid var(--black);
}

tbody td img {
    width: 100%;
    max-width: 100px;
}

tbody td input[type="checkbox"] {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    margin: 0;
    opacity: 0;
}

thead th {
    width: 33.3%;
    background-color: var(--second-bg);
    padding: 15px;
    border-radius: 8px;
    border-top: 0;
    border-right: 0;
    border-left: 0;
    border-bottom: 3px solid var(--black);
}

thead th div {
    display: flex;
    gap: 10px;
    align-items: center;
    justify-content: center;
}

thead th h2 {
    color: var(--black);
}

thead th img {
    max-width: 25px;
}

thead .rematch {
    cursor: pointer;
}

thead .rematch h2 {
    color: var(--gray);
}

tfoot td {
    text-align: center;
    padding: 10px;
    border-radius: 8px;
    color: var(--black);
    text-transform: uppercase;
}

tfoot .ties {
    background-color: var(--gray);
}

tfoot td span {
    margin: 0 2px;
}
</style>