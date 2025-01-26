# Contribution

- [Contribution](#contribution)
    - [Introduction](#introduction)
    - [Cutting the project](#cutting-the-project)
    - [How to make the commits](#how-to-make-the-commits)
    - [Contact](#contact)

## Introduction

To participate in the project you must:

- Fork the project so that it is among the directories of your github ex account: `https://github.com/your-account/app`
- Clone the project from your github `git clone account https://github.com/your-account/app`
- Create a branch whose name will be the summary of your change `git branch branch-of-your-works`
- Make a publication on your depot `git push origin branch-of-your-works`
- Finally make
  a [pull-request](https://www.thinkful.com/learn/github-pull-request-tutorial/Keep-Tabs-on-the-Project#Time-to-Submit-Your-First-PR)

## Cutting the project

The Bow framework project is split into a subproject. Then each participant will be able to participate on the section
in which he feels the best.

Imagine that you are more comfortable with the construction of Routing. Just focus on `src/Routing`. Note that the
sections have to be independent and therefore have the own configuration.

## How to make the commits

Commits validate your change. But in the Bow project, there is a way to write the commit message.

- `change` to add a change to a function
- `fix` to fix a bug
- `feat` to add a new feature

For example, you have worked on the `Session` section and you want to validate your changes.

To do it look a bit the nomenclature of a commit message:

```sh
git commit
```

In your editor:

```txt
change(module_name): your commit message

Description
```

In our previous example we will do:

```bash
git commit -m "change(session): rename init variable"
```

> The modification can also read an element in a section:

```bash
git commit -m "fix(http:request): bug fix #40"
```

In case your modification affect more section? You give a message and a description of the changes as a smart list.

## Contact

Please, if there is a bug on the project please contact me by email or leave me a message on
the [slack](https://bowphp.slack.com).
