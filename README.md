general-pattern
===============

General purpose command line log file analyzer

# Wat...

The idea behind this log sniffer was to build a general purpose, extensible log analyzer that can and will work with all sorts of text/log files. General Pattern is here and all it wants from you is a good healthy dose of regexes to be put in the `config.json` file. That said, you have to know PHP regex syntax in order to use this log sniffer.

#  Requirements and installation

General Pattern requires PHP version `5.3` or greater. (PHP `5.4` is highly recommended.)

To install, simply download the files or clone this repo.

# How to set up

Go and edit the `config.json` file. Some options will be explained below:

`file_extension`: If specified, only files of this extension will be picked up (only applies if you're passing directories for input).

`files`: A list of files and directories to analyze. The script will find files recursively. Example:
```json    
"files": [
    "/path/to/my/logs/dir",
    "/path/to/a/single/log/file.log"
]
```

`feature_map`: This defines a map to bind a metric type (see below) to an actual class that will analyze a line for mentioned metric. Currently, General Pattern supports two types of features, out of the box:
- counting how many times a certain pattern appears in the logs, and
- finding and listing duplicates in all of the input log files.

**Feel free to write your own feature!** The only two restrictions that apply are:
- it will have to be in the `GeneralPattern\Features` namespace and
- extend the `GeneralPattern\Features\Feature` base class.

Once that's done, add your new feature to the feature map in `config.json`, and you are ready to do your magic. Example:
```json
"feature_map": {
    "findDuplicates": "FindDuplicates",
    "getCount": "GetCount",
    "yourFeatureType": "YourClassToDoMagic"
}
```

# Metrics

Metrics are the information we want to extract from the logs. They will each be applied to every line that goes through the log sniffer, and their configuration varies depending on the feature. Below you can find examples for the two features that come with General Pattern by default:

## Metrics to find duplicates

Example: 
```json
{
    "type": "findDuplicates",
    "name": "Duplicate Lines",
    "match": [],
    "ignore": [],
    "in_files": "*"
}
```
- `type` here is the type name as defined in the feature_map config option (see above). This will tell the log sniffer which class to instantiate to extract the results from each processed line
- `name` will be the name of the results group in the output file
- `match` is an array of regexes you can use to cull results you don't want to see. Only the lines that match at least one of the regexes listed here will be processed. Leave empty to process all lines.
- `ignore` allows you to ignore certain parts of each processed line. All strings that match any of the regexes in the `ignore` array will be cut from all lines before comparing them together (e.g. this feature is useful to ignore time stamps, parameters in urls, etc. when comparing two lines).
- `in_files` will specify a list of files to apply this metric to. Leave the default "*" value to apply the metric to all input files.

## Metrics to count recurring strings that match a pattern

Example: 
```json
{
    "type": "getCount",
    "name": "API Calls By Popularity",
    "regex": "",
    "clean_up": [],
    "in_files": "*",
    "match_all": false,
    "sort": {
        "by": "value",
        "as": "number",
        "order": "desc"
    }
}
```
- `type` here is the type name as defined in the feature_map config option (see above). This will tell the log sniffer which class to instantiate to extract the results from each processed line
- `name` will be the name of the results group in the output file
- `regex` will define the pattern we are looking for in the logs
- `clean_up` is an array of substrings to be removed from the results after processing them. **Note**: items in the array should be simple strings, regexes will not be processed here.
- `in_files` will specify a list of files to apply this metric to. Leave the default "*" value to apply the metric to all input files.
- `match_all` will force the script to cound all occurrences of `regex` in the logs, not just the number of lines that have any match at all (as is the default behaviour).
- `sort` will allow you to apply sorting on the results before displaying them. You can sort by `name` or `value`.

# Usage

Once you're done configuring the log sniffer, all you have to do is call `/path/to/php ./fetch.php` from the command line.

# Performance notes

General Pattern has been tested on PHP versions `5.3.27` and `5.4.19`. Due to performance considerations we highly recommend you use PHP `5.4.*`, as in our tests, it has proven to be 8-10 times faster.

Also, it is generally a good idea to be generous when specifying memory limits for the analysis (see the default `config.json` file in the project), especially if you're using the `FindDuplicates` feature. When using this feature, GeneralPattern will need to have a memory limit of at least twice the combined size of your input log files.
