#!/usr/bin/python

# Steps to import a project
# - create users
# - import tickets
# - import ticket metadata
# - import comments
# - import attachments

from jira import JIRA
import getpass

# Set variables
username = raw_input('Username: ')
password = getpass.getpass("Password: ")
url = 'https://jira.atlassian.net'

# Initialize JIRA
jira = JIRA(url, basic_auth=(username, password))

# Initialize issue object
issue = jira.issue('TI-1')

print vars(issue)

# Get some issue details.
print issue.fields.project.id
print issue.fields.issuetype.name
print issue.fields.reporter.displayName
print issue.fields.issuetype

# Create a new issue
# issue_dict = {
#     'project': {'id': 11400},
#     'summary': 'New issue from jira-python',
#     'description': 'Look into this one',
#     'issuetype': {'name': 'IT Help'},
# }
# new_issue = jira.create_issue(fields=issue_dict)