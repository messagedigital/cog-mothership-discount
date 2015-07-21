$(function() {
	// Product table JS setup
	var dataTable = $('.table-filter.bundles').dataTable({
		iDisplayLength: 25,
		"oLanguage": {
			"sLengthMenu": 'Display <select>'+
			'<option value="25">25</option>'+
			'<option value="50">50</option>'+
			'<option value="100">100</option>'+
			'<option value="200">200</option>'+
			'<option value="-1">All</option>'+
			'</select> bundles',
			"sInfo": "Showing (_START_ to _END_) of _TOTAL_ bundles"}
	}).columnFilter({
		aoColumns: [
			{ type: "text" },
			null,
			{ type: "text" },
			{ type: "text" },
			{ type: "text" },
			{ type: "text" },
			{ type: "text" },
			null
		]
	});
});