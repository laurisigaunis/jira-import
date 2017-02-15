#!/usr/bin/python

from jira import JIRA
from pprint import pprint
from collections import OrderedDict
import getpass, json

# Set variables
username = raw_input('Username: ')
password = getpass.getpass("Password: ")
url = 'https://jira.atlassian.net'
projectID = raw_input('Project ID to import data in: ')

# Initialize JIRA connection
jira = JIRA(url, basic_auth=(username, password))

# Import the JSON file with issue data.
with open('cases.json') as json_data:
    data = json.load(json_data, object_pairs_hook=OrderedDict)
    # print(data['1'])

# Loop the JSON data array
print 'Creating issues...'
for nkey, node in data['nodes'].iteritems():
    # Node metadata available here
    # print node['title']

    # Create a new issue
    issue_dict = {
        'project': {'key': projectID},
        'summary': node['title'],
        'description': node['body'],
        'issuetype': {'name': 'IT Help'},
    }
    issue = jira.create_issue(fields=issue_dict)
    print '[Issue] ' + issue.key + ' created'

    # Loop the comments array
    print 'Adding comments...'
    if 'comments' in node:
        for ckey, comment in node['comments'].iteritems():
            # Comment metadata available here
            comment_text = 'On %s %s said:\n %s' % (comment['timestamp'], comment['user_name'], comment['comment'])
            print 'comment id  '+ ckey

            # Add a new comment
            comment = jira.add_comment(issue, comment_text)
    #Set issue status (Closed, Pending, Open, ..)
    #Before execute this, check correct available status id's. Comment next line and default will be 'Open' status
    jira.transition_issue(issue.key, node['jira_transition_id'])
