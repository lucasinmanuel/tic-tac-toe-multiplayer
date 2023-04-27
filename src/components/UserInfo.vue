<template>
    <div class="user-info">
        <div id="search-wrapper">
            <img v-on:click="emitSearch" :src="imgSearch" />
            <input v-on:keydown.enter="emitSearch" v-model="searchValue" placeholder="Search opponent by id" type="number"
                name="opponent" />
        </div>
        <div id="user-status" v-if="json">
            <h1><b>Your Id: </b>{{ json.data.your_id }}</h1>
            <button v-on:click="emitReflesh()" type="button">Refresh</button>
        </div>
        <div id="opponent-list">
            <ul v-if="json">
                <template v-for="opponent_id in json.data.opponent_list">
                    <li>
                        <b>{{ opponent_id }}</b>
                        <button class="challenge-btn" v-on:click="emitChallenge(opponent_id)"
                            type="button">Challenge</button>
                    </li>
                </template>
            </ul>
            <div id="info-page">
                <span v-on:click="emitNavigation('previous')" id="previous-arrow">&#10094; Previous</span>
                <span v-if="json">{{ json.data.page }} / {{ json.data.number_pages }}</span>
                <span v-on:click="emitNavigation('next')" id="next-arrow">Next &#10095;</span>
            </div>
        </div>
    </div>
</template>

<script>
import imgSearch from "../assets/icon-search.svg"

export default {
    name: "UserInfo",
    props: {
        json: null
    },
    data() {
        return {
            searchValue: null,
            imgSearch: imgSearch
        }
    },
    methods: {
        emitReflesh() {
            this.$emit("emitReflesh");
        },
        emitChallenge(opponent_id) {
            this.$emit("emitChallenge", opponent_id);
        },
        emitSearch() {
            this.$emit("emitSearch", this.searchValue);
        },
        emitNavigation(direction) {
            this.$emit("emitNavigation", {
                direction: direction, value: this.searchValue
            });
        }
    }
}
</script>

<style>
.user-info {
    width: 45%;
    color: var(--black);
    background-color: var(--second-bg);
    border-radius: 8px;
    padding: 20px;
}

#search-wrapper {
    display: flex;
    border: 2px solid var(--black);
    border-radius: 8px;
}

#search-wrapper img {
    max-width: 50px;
    margin: 5px 10px 5px 5px;
    cursor: pointer;
}

#search-wrapper input {
    width: 100%;
    border-radius: 8px;
    font-size: 18px;
    background-color: transparent;
    border: 0;
    outline: none;
    color: var(--gray);
    font-weight: bold;
}

#user-status {
    width: 100%;
    display: flex;
    gap: 20px;
    margin: 20px 0;
    align-items: center;
    justify-content: center;
}

#user-status button {
    background-color: var(--gray);
    padding: 10px;
    border-top: 0;
    border-right: 0;
    border-left: 0;
    border-bottom: 3px solid var(--dark-gray);
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    color: var(--black);
}

#opponent-list ul {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    list-style-type: none;
}

#opponent-list li {
    width: calc(33.3% - 7px);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-size: 28px;
    color: var(--black);
    font-weight: bold;
    border: 2px solid var(--black);
    border-radius: 8px;
    padding: 8px;
}

#opponent-list li button {
    background-color: var(--yellow);
    padding: 10px;
    border-top: 0;
    border-right: 0;
    border-left: 0;
    border-bottom: 3px solid var(--dark-yellow);
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    color: var(--black);
}

#info-page {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}

#info-page span {
    font-size: 20px;
    font-weight: bold;
}

#previous-arrow,
#next-arrow {
    cursor: pointer;
}
</style>