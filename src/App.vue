<template>
  <header></header>
  <main>
    <div class="container">
      <UserInfo @emitReflesh="reflesh" @emitChallenge="challenge" @emitSearch="search" @emitNavigation="navigation"
        :json="player_list" />
      <GameWrapper @emitChoice="choice" @emitRematch="rematch" :json="game" />
    </div>
  </main>
  <!-- <HelloWorld msg="Vite + Vue" /> -->
</template>

<script>
import UserInfo from './components/UserInfo.vue';
import GameWrapper from './components/GameWrapper.vue';
export default {
  name: "App",
  components: {
    UserInfo,
    GameWrapper
  },
  data() {
    return {
      socket: new WebSocket('ws://localhost:9990/'),
      player_list: null,
      game: null
    }
  },
  methods: {
    reflesh() {
      this.socket.send(JSON.stringify({ type: "refresh" }));
    },
    challenge(opponent_id) {
      //ENVIA O CONVITE PARA O PARTIDA
      this.socket.send(JSON.stringify({
        type: "challenge-invite",
        addressee_id: opponent_id
      }))
    },
    choice(json) {
      this.game = json
      this.game["type"] = "challenge-update"
      this.socket.send(JSON.stringify(this.game))
    },
    rematch() {
      this.game["type"] = "rematch-invite"
      this.socket.send(JSON.stringify(this.game))
    },
    search(value) {
      this.socket.send(JSON.stringify({ type: "search-value", value: value }))
    },
    navigation(json) {
      if (this.player_list.type == "receive-search" || this.player_list.type == "page-navigation-search") {
        this.socket.send(JSON.stringify({ type: "page-navigation-search", direction: json.direction, value: json.value, page: this.player_list.data.page }))
      } else {
        this.socket.send(JSON.stringify({ type: "page-navigation", direction: json.direction, page: this.player_list.data.page }))
      }
    }
  },
  mounted() {
    this.socket.addEventListener("message", (event) => {
      const json = JSON.parse(event.data);
      if (json.type == "challenge-receive") {
        //RECEBE O DESAFIO DE ALGUM JOGADOR, CASO SEJA ACEITO A RESPOTA SERÁ ENVIADA PARA O BACK-END
        let bool = confirm("Você foi desafiado pelo jogador " + json.sender_id + ". Aceita?")
        if (bool) {
          json.type = "challenge-accepted";
          this.socket.send(JSON.stringify(json));
        }
      } else if (json.type == "rematch-receive") {
        //RECEBE A REVANCHE DE ALGUM JOGADOR, CASO SEJA ACEITO A RESPOTA SERÁ ENVIADA PARA O BACK-END
        let bool = confirm("Você foi desafiado para uma revanche. Aceita?")
        if (bool) {
          json.type = "rematch-accepted";
          this.socket.send(JSON.stringify(json));
        } else {
          this.game = null;
        }
      } else if (["challenge-started", "rematch-started", "challenge-update"].includes(json.type)) {
        //RECEBE AS INFORMAÇÕES DA PARTIDA QUE VEM DO BACK-END
        //ATUALIZA AS ESCOLHAS FEITAS NO DESAFIO EM TEMPO REAL
        this.game = {}
        for (let i in json) {
          if (typeof (json[i]) == "object") {
            if (json[i].id == this.player_list.data.your_id) {
              this.game["you"] = json[i]
            } else {
              this.game["opponent"] = json[i]
            }
          } else {
            this.game[i] = json[i]
          }
        }
        if (json.type == "rematch-started") {
          alert("Desafio iniciado!")
        }
      } else if (["init", "refresh", "receive-search", "page-navigation", "page-navigation-search"].includes(json.type)) {
        //CASO O SITE SEJA ABERTO OU UM REFLESH ACONTEÇA O ESTADO DO this.player_list É ATUALIZADO
        this.player_list = json;
      } else if (json.type == "opponent-disconnected") {
        alert("O seu oponente desconectou!")
        this.game = null;
      }
    });
  }
}
</script>

<style>
@media screen and (max-width: 1400px) {
  main .container {
    flex-direction: column;
  }

  .user-info {
    width: 60%;
  }

  .game-wrapper {
    width: 60%;
  }
}

@media screen and (max-width: 1024px) {
  main .container {
    flex-direction: column;
  }

  .user-info {
    width: 100%;
  }

  .game-wrapper {
    width: 100%;
  }
}

@media screen and (max-width: 720px) {
  #opponent-list li {
    width: 50%;
  }
}

@media screen and (max-width: 420px) {
  #opponent-list li {
    width: 100%;
  }
}
</style>
