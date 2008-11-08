<html>
<head>
<title><?php echo $title ?></title>
</head>
<style type="text/css">
h1 {
	font-size: medium;
}

body {
	font-family: "Gill Sans MT", "Gill Sans", GillSans, Arial, Helvetica,
		sans-serif;
}

td.percentage {
	text-align: right;
}

caption {
	border-bottom: thin solid;
	font-weight: bolder;
}

dt {
	font-weight: bolder;
}

table {
	margin: 1em;
}
</style>
<body>
<h1 id="title"><?php $title ?></h1>
<table>
	<caption>Summary</caption>
	<tbody>
		<tr>
			<td>Total Coverage (<a href="#total-coverage">?</a>) :</td>
			<td class="percentage"><span class="totalPercentCoverage"><?php echo $totalPercentCoverage ?>%</span></td>
		</tr>
		<tr>
			<td>Total Files Covered (<a href="#total-files-covered">?</a>) :</td>
			<td class="percentage"><span class="filesTouchedPercentage"><?php  echo $filesTouchedPercentage ?>%</span></td>
		</tr>
		<tr>
			<td>Report Generation Date :</td>
			<td><?php echo $now ?></td>
		</tr>
	</tbody>
</table>
<table id="covered-files">
	<caption>Coverage (<a href="#coverage">?</a>)</caption>
	<thead>
		<tr>
			<th>File</th>
			<th>Coverage</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($coverageByFile as $file => $coverage) { ?>
		<tr>
			<td><a class="byFileReportLink" href="<?php echo $coverage['byFileReport']  ?>"><?php echo $file ?></a></td>
			<td class="percentage"><span class="percentCoverage"><?php echo $coverage['percentage'] ?></span></td>
		</tr>
		<?php } ?>
	</tbody>
</table>
<table>
	<caption>Files Not Covered (<a href="#untouched">?</a>)</caption>
	<tbody>
		<?php foreach ($untouched as $key => $file) { ?>
		<tr>
			<td><span class="untouchedFile"><?php echo $file ?></span></td>
		</tr>
		<?php } ?>
	</tbody>
</table>

<h2>Glossary</h2>
<dl>
	<dt><a name="total-coverage">Total Coverage</a></dt>
	<dd>Ratio of all the lines of executable code that were executed to the
	lines of code that were not executed. This does not include the files
	that were not covered at all.</dd>
	<dt><a name="total-files-covered">Total Files Covered</a></dt>
	<dd>This is the ratio of the number of files tested, to the number of
	files not tested at all.</dd>
	<dt><a name="coverage">Coverage</a></dt>
	<dd>These files were parsed and loaded by the php interpreter while
	running the tests. Percentage is determined by the ratio of number of
	lines of code executed to the number of possible executable lines of
	code. "dead" lines of code, or code that could not be executed
	according to xdebug, are counted as covered because in almost all cases
	it is the end of a logical loop.</dd>
	<dt><a name="untouched">Files Not Covered</a></dt>
	<dd>These files were not loaded by the php interpreter at anytime
	during a unit test. You could consider these files having 0% coverage,
	but because it is difficult to determine the total coverage unless you
	could count the lines for executable code, this is not reflected in the
	Total Coverage calculation.</dd>
</dl>

<p>To generate your own code coverage download <a
	href="http://acquia.com/files/test-results/drupal-cli-utils.tgz">drupal-cli-utils.tgz</a></p>

</body>
</html>
