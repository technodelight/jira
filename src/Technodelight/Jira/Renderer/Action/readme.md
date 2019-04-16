transition

```
Task HDMAM-1874 has been successfully moved to Deploy for QA and has been assigned to spandey
link: https://inviqa-de.atlassian.net/browse/HDMAM-1874
branches:
    feature/HDMAM-1874-translate-filter-labels (remote)

Transitions to move from this state:
┌─────────────┬────────────────────────────────┐
│ Transition  │ Command                        │
├─────────────┼────────────────────────────────┤
│ Open (2)    │ jira workflow:open HDMAM-1874  │
│ Closed      │ jira workflow:close HDMAM-1874 │
│ Start QA    │ jira workflow:in-qa HDMAM-1874 │
│ Close Issue │                                │
└─────────────┴────────────────────────────────┘
```

change
```
Unchanged 1.62.2 for Fix Version/s
Added 1.63.0 for Fix Version/s
```

pr
```
You have successfully created PR #2958 (https://github.com/inviqa/heidelberg/pull/2958)
Labels added: Ready for review
Milestone added: 1.66.0
```

assign
```
HDMCR-1012 was assigned successfully to gabor.szabo
```

colors:
- issueKey: green
- summary: white (default)
- label: orange
  - action/actor: green
  - command: cyan in brackets
  - misc details: orange in brackets
  - hidden: black foreground

order is always orange -> green -> cyan
```
transitions: (orange)
  Ready for development (green) [command] (cyan)
```

- display hints?

- minimal header should be rendered after each change
