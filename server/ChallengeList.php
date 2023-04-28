<?php

namespace Config;

class ChallengeList
{
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function add($sender_id, $addressee_id): array
    {
        $match_id = $sender_id . $addressee_id;
        $this->data = [
            "$match_id" => [
                "sender" => array(
                    "id" => $sender_id,
                    "nickname" => "sender",
                    "icon" => "x",
                    "choices" => [],
                    "victories" => 0
                ),
                "addressee" => array(
                    "id" => $addressee_id,
                    "nickname" => "addressee",
                    "icon" => "o",
                    "choices" => [],
                    "victories" => 0
                ),
                "ties" => 0
            ]
        ];
        return $this->data[$match_id];
    }

    public function findOutMatchId($match): string
    {
        $match_id = [];
        foreach ($match as $value) {
            if (is_object($value)) {
                if ($value->nickname == "sender") {
                    $match_id[0] = $value->id;
                } else if ($value->nickname == "addressee") {
                    $match_id[1] = $value->id;
                }
            }
        }
        $match_id = $match_id[0] . $match_id[1];
        return $match_id;
    }

    public function deleteChallenge($your_id): int
    {
        $match_id = -1;
        $opponent_id = -1;
        foreach ($this->data as $value) {
            //VERIFICANDO SE VOCÊ ESTÁ EM UMA PARTIDA
            if (in_array($your_id, [$value["sender"]["id"], $value["addressee"]["id"]])) {
                $match_id = $value["sender"]["id"] . $value["addressee"]["id"];

                //VERIFICANDO QUAL É O ID DO OPONENTE
                $opponent_id = $your_id != $value["sender"]["id"] ? $value["sender"]["id"]
                    : $value["addressee"]["id"];
            }
        }
        //DELETANDO PARTIDA
        $index = array_search($match_id, $this->data);
        array_splice($this->data, $index, 1);

        //RETORNANDO O ID PARA ALERTAR O OPONENETE
        return $opponent_id;
    }

    public function getAddresseeId($match): int
    {
        $addressee_id = -1;
        foreach ($match as $value) {
            if (is_object($value)) {
                if ($value->nickname == "addressee") {
                    $addressee_id = $value->id;
                }
            }
        }
        return $addressee_id;
    }

    public function getSenderId($match): int
    {
        $sender_id = -1;
        foreach ($match as $value) {
            if (is_object($value)) {
                if ($value->nickname == "sender") {
                    $sender_id = $value->id;
                }
            }
        }
        return $sender_id;
    }

    public function randomTurnChoice($sender_id, $addressee_id): int
    {
        $result = [];
        array_push($result, $sender_id);
        array_push($result, $addressee_id);
        $index = array_rand($result, 1);
        return $result[$index];
    }

    public function victoryVerification($your_choices, $opponent_choices): string
    {
        $result = "continue";

        $choices = implode(",", $your_choices);

        $combinations = [
            ["A0", "B0", "C0"], ["A1", "B1", "C1"], ["A2", "B2", "C2"],
            ["A0", "A1", "A2"], ["B0", "B1", "B2"], ["C0", "C1", "C2"],
            ["A0", "B1", "C2"], ["A2", "B1", "C0"]
        ];

        foreach ($combinations as $combination) {
            if (
                str_contains($choices, $combination[0]) &&
                str_contains($choices, $combination[1]) &&
                str_contains($choices, $combination[2])
            ) {
                $result = "victories";
            }
        }

        if (sizeof($your_choices) >= 5 && sizeof($opponent_choices) >= 4 && $result != "victories") {
            $result = "ties";
        }

        return $result;
    }

    public function update($match_id, $your_key, $your_choices)
    {
        $this->data[$match_id][$your_key]["choices"] = $your_choices;
        return $this->data[$match_id];
    }

    public function endMatch($match_id, $your_key, $your_choices, $result)
    {
        $this->data[$match_id][$your_key]["choices"] = $your_choices;
        if ($result == "victories") {
            $this->data[$match_id][$your_key][$result] += 1;
        } else if ($result == "ties") {
            $this->data[$match_id][$result] += 1;
        }
        return $this->data[$match_id];
    }
}
