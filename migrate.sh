#!/usr/bin/python

from jira import JIRA
from pprint import pprint
import getpass, json


### Initialize JIRA connection

# Set variables
username = raw_input('Username: ')
password = getpass.getpass("Password: ")
url = 'https://jira.atlassian.net'
projectID = raw_input('Project ID to import data in: ')

# Initialize JIRA
jira = JIRA(url, basic_auth=(username, password))

# Import the JSON file with issue data.
with open('cases.json') as json_data:
    data = json.load(json_data)
    # print(data['1'])

# Loop the JSON data array
print 'Creating issues...'
for nkey, node in data['nodes'].iteritems():
    # Node metadata available here
    # print node['title']

    # Create a new issue
    issue_dict = {
        'project': {'id': projectID},
        'summary': node['title'],
        'description': node['body'],
        'issuetype': {'name': 'IT Help'},
    }
    issue = jira.create_issue(fields=issue_dict)
    print '[Issue] ' + issue.key + ' created'

    # Loop the comments array
    print 'Adding comments...'
    for ckey, comment in node['comments'].iteritems():
        # Comment metadata available here
        # print comment['comment']

        # Add a new comment
        comment = jira.add_comment(issue, comment['comment'])

