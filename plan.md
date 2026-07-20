# Ray.Di 2.23.0 — binding diagnostics と runtime JIT 廃止

Ray.Di / Ray.Compiler / Ray.ObjectGrapher にまたがる作業計画。
最終更新: 2026-07-20

## 決定した最終アーキテクチャ

境界は **「記録 + 公開 + テキスト成果物 = core、探索 UI = 外部」**。

束縛情報は library 利用者にとって optional な diagnostics ではなく、Ray.Di が
常に提供すべき成果物として扱う。人間と AI エージェントが、PHP を実行せず全
Module を探索せずに、実際に compose された束縛を安定したファイルとして読める
ことを保証する。

根拠:

- provenance の記録(`BindingLog`、`BindingEvent`)と render 基盤(`ModuleString`、
  各 Dependency の `__toString()`)はすでに core にある。Markdown writer だけを
  外部 package に置いても実削減はほぼゼロで、バージョン整合・リリース同期・
  「install しないと読めない」という発見性のコストだけが残る
- optional な成果物はエージェントのワークフローに組み込めない。「scriptDir を
  見れば bindings.md が必ずある」という保証があって初めて機能する
- Injector はすでに classDir へ proxy class を書いている。テキスト 1 枚の追加は
  新しい種類の副作用ではない

「必須」は二層で定義する。

| path | 位置づけ | 失敗時 |
| --- | --- | --- |
| Ray.Compiler の compile 時 | build 成果物として必須 | fatal |
| 通常 Injector の composition 時 | 明示 scriptDir があるときだけ | best-effort |

これにより「production の Injector は単純・決定的・安定」という価値観と、
「成果物は必ず存在する」という要請を両立する。

## 各 package の責務

### ray/di (core)

- `BindingLog` / `BindingEvent` による composition-time provenance
- `Ray\Di\ModuleVisitorInterface` + `AbstractModule::accept()` —
  外部ツールが合成済み Container へアクセスする唯一の公式経路
- `Ray\Di\BindingsMarkdown` — bindings.md writer(2.22 と同名・同 API の本実装)
- runtime JIT binding なし

### ray/compiler

- `Compiler::compile()` で bindings.md を生成。失敗は compile 失敗(fatal)
- 既存の `compile.lock` flock の**内側**で書くので並行安全性は追加コストなし
- 書く対象は `CompilerModule` override 後の container = CompiledInjector が
  実際に使う実態
- `CompiledInjector` は bindings.md を読みも書きもしない(完全 read-only)。
  compile 時に生成済みのファイルが scriptDir にあるので利用者は成果物を持てる

### ray/object-visual-grapher

Ray\Bindings の高度な機能をここへ統合する(新規 package は作らない)。

- `Ray\ObjectGrapher` — DOT dependency graph(既存 API は完全維持)
- `Ray\Bindings` — HTML viewer、CSS/JS、`bindings-html` CLI、2.22.2 互換クラス
- `Ray\Di\BindingsMarkdown` 互換(`src-deprecated/di/`)

require-dev で導入する開発時ツール。production runtime には関与しない。

## bindings.md の生成ライフサイクル

```text
Module composition / compile
    ↓
bindings.md を生成(atomic、signature 一致ならスキップ)
    ↓
Injector::getInstance()   ← runtime I/O なし
    ↓
人間・AI が bindings.md を読む
```

- **通常 Injector**: コンストラクタ内 `container->sort()` の直後に一度だけ。
  条件は `is_dir($tmpDir)` が真、つまり明示的に application が所有する
  scriptDir があるときのみ
- **Ray.Compiler**: `compile()` 内、flock の内側
- **CompiledInjector**: 何もしない

### 書き込み設計

- **atomic write**: tmp file + `rename()`。並行 process でも torn file にならない
- **signature を md 末尾の HTML コメントに埋め込む 1 ファイル方式**
  (`<!-- signature: sha256hex -->`)。別ファイル方式にあった md/signature の
  不整合 race を排除し、読み手にも無害
- **signature の入力は resolved bindings + pointcuts のみ**。provenance は
  意図的に除外する。`BindingLog` は serialize 対象外で revive 後に空になるが、
  signature が一致するため再書き込みされず、初回の充実した provenance が劣化
  しない
- 内容が同じなら render ごとスキップ
- runtime path は best-effort(`Throwable` を握りつぶす)。diagnostics のために
  本番アプリを止めない

### public contract の範囲

| 対象 | 扱い |
| --- | --- |
| ファイルの存在と場所(`{scriptDir}/bindings.md`) | contract |
| 決定性(同一入力 → 同一バイト列、sort 済み、timestamp なし) | contract |
| 字面フォーマット | minor で改善可。`SIGNATURE_VERSION` で管理 |

機械的な統合は `accept()` で Container を直接読む。bindings.md は人間と
エージェント向けであり、パースさせる前提にしない。したがって JSON manifest は
作らない(canonical source は Container そのもの)。

## scriptDir 未指定時の扱い

`sys_get_temp_dir()` フォールバック時は **生成しない**。

理由: 場所が予測不能になると「エージェントが見つけられる成果物」という目的
自体が壊れる。複数 application の衝突とゴミ蓄積も招く。hash 付きファイル名で
回避する案も同じ理由で却下。

「bindings.md が欲しければ明示的な tmpDir を渡す」を推奨プラクティスに
格上げする。

## directory / namespace 構成

### ray/di

```text
src/di/BindingsMarkdown.php        Ray\Di\BindingsMarkdown(本実装)
src/di/ModuleVisitorInterface.php  Ray\Di\ModuleVisitorInterface
tests/di/BindingsMarkdownTest.php
```

`ModuleVisitorInterface` は `visit(Container): void` の最小形で確定。
binding ごとのコールバックを持つ細粒度 visitor は YAGNI。`accept()` の本質的
価値は「configure 済み・合成完了後の Container を渡す」ライフサイクル保証に
ある。dependency 構造用の既存 `VisitorInterface` とも名前で区別できる。

### ray/object-visual-grapher

```text
src/                  Ray\ObjectGrapher
src-bindings/         Ray\Bindings
src-deprecated/di/    Ray\Di\BindingsMarkdown(互換)
tests/
tests-bindings/
```

```json
{
  "autoload": {
    "psr-4": {
      "Ray\\ObjectGrapher\\": "src/",
      "Ray\\Bindings\\": "src-bindings/",
      "Ray\\Di\\": "src-deprecated/di/"
    }
  }
}
```

1 package 2 namespace は、「束縛を理解する」という 1 つの目的に Markdown /
HTML / DOT という 3 つの出力形式がある、と整理すれば 2 つの顔ではなく 1 つの顔
になる。

## release 順序

論理的制約は 1 つだけ: **runtime JIT 削除は bindings.md 常時生成より先か同時**。
JIT が残ると Container が実行中に変化し、「bindings.md = 合成の真実」が仕様
レベルで成立しない。逆順のみ不可。

### 1. Ray.Di 2.23.0 — 実装完了、未 push

branch: `extract-bindings-package`(2.22.2 = 236ac262 の上に 4 commit)

| commit | 内容 |
| --- | --- |
| 29d82ba6 | Move binding diagnostics out of core |
| 445d85f4 | Pin the fail-fast contract for unbound concrete classes(red) |
| 4c839fd3 | Remove runtime just-in-time binding(green にする fix) |
| be94a8cf | Write bindings.md at composition time |

検証済み: 262 tests / 434 assertions、coverage 100%
(classes 50/50、methods 215/215、lines 887/887)、
PHPStan / Psalm / phpcs / phpmd すべて通過。

実装中に既存バグを 1 件発見・修正: interceptor を**インスタンス**で bind すると
`SpyCompiler` が文字列化に失敗し、2.22.2 の Ray\Bindings でも render が壊れて
いた(移植元にこのケースのテストが無かった)。class 名に正規化して解決済み。

runtime JIT 削除の元差分は `stash@{0}` に backup として保持(apply のみ、
drop していない)。

残: push、PR、CodeRabbit review、リリース。

### 2. Ray.ObjectGrapher 1.1.0

**2.0 ではなく 1.x の minor にする。** bear/package の制約が `^1.0` なので、
2.0 にすると bear/package 側の composer.json 更新とリリースが必要になり、
自動配布が切れる。1.1.0 なら何もしなくても届く。

手順:

1. PR #4(`Keep object graph analysis non-destructive`)を先にマージ。
   `bindOnTheFly()` の除去は JIT 廃止と同方向で、統合の前提
2. patch release(現行ユーザーへ非破壊 fix を届ける)
3. composer.json 近代化: `require` に `php ^8.2` と `ray/di ^2.23` を追加。
   現状 ray/di が require-dev にしかないのは宣言として壊れており、これは修正
   でもある。古い環境は composer が 1.0.0 に解決するので壊れない
4. ローカル `/Users/akihito/git/Ray.Bindings` のコード・テスト
   (35 tests / 95 assertions、coverage 100%)を移植。この package 自体は
   公開せず役目を終える
5. README を「Ray.Di binding inspection and visualization」として再定義

### 3. Ray.Compiler minor

- `ray/di ^2.23` へ更新
- `Compiler::compile()` の flock 内で bindings.md 生成
- CompiledInjector には reflection fallback も runtime compile も追加しない

### 4. bear/package・BEAR.ApiDoc

**作業不要。** 制約を満たす環境から順に 1.1.0 が自動で届く。

## リスクと回避策

| リスク | 回避策 |
| --- | --- |
| コンストラクタ毎の signature 計算コスト | 明示 tmpDir 時のみ発動。数百束縛で sub-ms。高負荷アプリの答えは従来どおり Ray.Compiler(compile 時 1 回) |
| 並行 process の書き込み race | atomic rename + same-content skip。実害は「同内容を二重に書く」まで。Compiler 側は既存 flock 内 |
| 2.22.2 → 2.23.0 の BC(`Ray\Bindings\*` 削除) | リリース間隔が短く利用者が増える前。リリースノートに 1:1 の移行表を載せる |
| bindings.md への機械パース依存の発生 | 「機械的統合は `accept()` で Container を直接読む」と明記し、字面を contract にしない |
| read-only filesystem(Lambda 等) | runtime は best-effort なので落ちない。必須性は build path で担保する二層設計そのものが回避策 |
| ObjectGrapher の package 名と namespace の乖離 | README と Packagist description で補う。rename は後からでもできるが、配布チャネルは今ここにある |

## 採用しなかった代替案

| 案 | 却下理由 |
| --- | --- |
| bindings.md を optional dev package に置く | エージェント保証が成立しない。render 基盤が core に残る以上、境界が薄すぎて package 境界のコストに見合わない |
| 新規 `ray/bindings` package を公開し ObjectGrapher を abandon | bear/package が **require**(require-dev ではない)で `^1.0` を持ち、月 11,668 DL・累計 45.9 万。abandon すれば BEAR 全アプリに警告が出る。回避には全エコシステムの付け替えが必要で、対価が「名前の正しさ」だけになる |
| JSON manifest を併設 | YAGNI。機械の canonical source は Container(accept 経由)。対象読者(人間 + LLM)には決定的 Markdown 1 形式が最適 |
| `sys_get_temp_dir()` でも hash 付きファイル名で生成 | 場所が予測不能になり目的自体を壊す。ゴミ蓄積も招く |
| `getInstance()` 時の lazy 生成 | runtime I/O の持ち込み。決定性と「resolution と生成の分離」原則に反する |
| runtime でも書き込み失敗を fatal に | diagnostics のために本番アプリを止めるのは本末転倒。必須性は build で担保 |
| 3.x major での整理 | 不要。すべて minor で吸収でき、15 年の minor 運用の価値観に反する理由がない |
| JIT 削除と bindings.md を 2 つの minor に分割 | 2.22.2 にあった bindings 機能が suggest 頼みになる空白期が生まれる。writer は移植なので追加実装は小さく、1 minor に統合するのが一貫した物語になる |

## 関連 URL

- Ray.Di 2.22.2: https://github.com/ray-di/Ray.Di/releases/tag/2.22.2
- ObjectGrapher PR #4: https://github.com/ray-di/Ray.ObjectGrapher/pull/4
