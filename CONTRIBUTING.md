# Contribution

- [Introduction](#introduction)
- [Découpage du projet](#decoupage-du-projet)
- [Comment faire les commits](#comment-faire-les-commits)

## Introduction

Pour participer au projet il faut:

- Fork le projet afin qu'il soit parmi les répertoires de votre compte github ex :`https://github.com/votre-compte/app`
- Cloner le projet depuis votre compte github `git clone https://github.com/votre-crompte/app`
- Créer un branche qui aura pour nom le résumé de votre modification `git branch branche-de-vos-traveaux`
- Faire une publication sur votre dépot `git push origin branche-de-vos-traveaux`
- Enfin faire un [pull-request](https://www.thinkful.com/learn/github-pull-request-tutorial/Keep-Tabs-on-the-Project#Time-to-Submit-Your-First-PR)


## Découpage du projet

Le projet Bow framework est découper en sous projet. Alors chaque participant poura participer sur la section dans laquelle il se sens le mieux.

Imaginons que vous etes plus confortable avec la construction des Routing. Il suffit de vous concentrer sur `src/Routing`. Notez que les sections ont faire pour être indépendant et donc possède le propre configuration.

## Comment faire les commits

Les commits permettent de valider votre modification. Mais dans le projet Bow, il y a une façon d'écrire le message de commit. Prenons un exemple, vous avez travailler sur la section `Session` et vous voulez valider vos modification.

Pour le faire regardez un peu la nomenclature d'un message de commit:

```sh
git commit
```

Dans votre éditeur:

```
[nom-de-la-section] message de commit

Description
```

Dans notre exemple précédant nous allons donc faire:

```
git commit -m "[session] message de modification"
```

La modification peut aussi affecture un element dans un section:

```
git commit -m "[http:request] bug fix #40"
```

Dans le cas ou votre modification affect plusieur section ? Vous donnez un message et un description des modifications sous forme de liste à puce.

## Auteurs

Liste des contributeurs:

- Franck Dakia <dakiafranck@gmail.com> [@franck_dakia](https://twitter.com/franck_dakia)
- Ayiyikoh <fablab@ayiyikoh.org> [@ayiyikoh](https://twitter.com/ayiyikoh) hashtag: __#GoAyiyikoh__

## Contact

SVP s'il y a un bogue sur le projet veuillez me contacter par email ou laissez moi un message sur le [slack](https://bowphp.slack.com).