function toggleDisplay(elementId, style) {
    document.getElementById(elementId).style.display = style;
}

function toggleOptions(className, style, labelId, label, onclickFunction) {
    var rows = document.getElementsByClassName(className);

    for (var i = 0; i < rows.length; i++) {
        rows[i].style.display = style;
    }

    var labelElement = document.getElementById(labelId);
    labelElement.innerHTML = label;
    labelElement.setAttribute("onclick", "javascript: " + onclickFunction + "; return false;");
}

function showAdvanceOptions() {
    toggleOptions('advance-options', 'table-row', 'advanceOptionsLabel', 'Hide Advanced Options', 'hideAdvanceOptions()');
}

function hideAdvanceOptions() {
    toggleOptions('advance-options', 'none', 'advanceOptionsLabel', 'Show Advanced Options', 'showAdvanceOptions()');
}

function moreFilterOptions() {
    toggleOptions('filter-options', 'table-row', 'moreFilterOptionsLabel', 'Hide Filter Options', 'lessFilterOptions()');
}

function lessFilterOptions() {
    toggleOptions('filter-options', 'none', 'moreFilterOptionsLabel', 'Show Filter Options', 'moreFilterOptions()');
}

function showRangeFields() {
    document.getElementById('postRange').style.display = 'block';
}

function hideRangeFields() {
    document.getElementById('postRange').style.display = 'none';
}

function setMinValueForPostsUptoField() {
    if (document.getElementById('posts-from').value != "") {
        document.getElementById('posts-upto').setAttribute('min', document.getElementById('posts-from').value);
    }
}

/* ----------------------------------------------------------------------
 * Export-field helpers: presets and per-group select all / none.
 * -------------------------------------------------------------------- */

function eauFieldCheckboxes() {
    return document.querySelectorAll('input[name="export_fields[]"]');
}

/* Open any <details> group that now contains a checked field. */
function eauSyncGroupOpenState() {
    var groups = document.querySelectorAll('details.eau-field-group');
    for (var i = 0; i < groups.length; i++) {
        var checked = groups[i].querySelector('input[name="export_fields[]"]:checked');
        if (checked) {
            groups[i].setAttribute('open', 'open');
        }
    }
}

function eauApplyPreset(keysCsv) {
    var wanted = keysCsv.length ? keysCsv.split(',') : [];
    var boxes = eauFieldCheckboxes();
    for (var i = 0; i < boxes.length; i++) {
        boxes[i].checked = (wanted.indexOf(boxes[i].value) !== -1);
    }
    eauSyncGroupOpenState();
}

function eauSetGroup(groupKey, state) {
    var boxes = document.querySelectorAll('input[name="export_fields[]"][data-eau-group="' + groupKey + '"]');
    for (var i = 0; i < boxes.length; i++) {
        boxes[i].checked = state;
    }
}

/* Paginate the on-screen results table in the browser. Page size comes from
 * the .eau-perpage selector (100/250/500/750/1000/all); default is 100. */
function eauPaginateResults() {
    var table = document.getElementById('outputData');
    if (!table || !table.tBodies.length) {
        return;
    }

    var rows = Array.prototype.slice.call(table.tBodies[0].rows);
    var nav = document.querySelector('.eau-pagination');
    var select = document.querySelector('.eau-perpage');
    var prev = nav ? nav.querySelector('.eau-prev') : null;
    var next = nav ? nav.querySelector('.eau-next') : null;
    var indicator = nav ? nav.querySelector('.eau-page-indicator') : null;

    var current = 1;

    function pageSize() {
        if (!select || select.value === 'all') {
            return rows.length || 1;
        }
        var n = parseInt(select.value, 10);
        return (n > 0) ? n : (rows.length || 1);
    }

    function totalPages() {
        return Math.max(1, Math.ceil(rows.length / pageSize()));
    }

    function render() {
        var size = pageSize();
        var pages = totalPages();
        if (current > pages) {
            current = pages;
        }
        var start = (current - 1) * size;
        var end = current * size;
        for (var i = 0; i < rows.length; i++) {
            rows[i].style.display = (i >= start && i < end) ? '' : 'none';
        }
        if (nav) {
            if (pages > 1) {
                nav.style.display = '';
                if (indicator) { indicator.textContent = current + ' / ' + pages; }
                if (prev) { prev.disabled = (current === 1); }
                if (next) { next.disabled = (current === pages); }
            } else {
                nav.style.display = 'none';
            }
        }
    }

    if (prev) {
        prev.addEventListener('click', function () {
            if (current > 1) { current--; render(); }
        });
    }
    if (next) {
        next.addEventListener('click', function () {
            if (current < totalPages()) { current++; render(); }
        });
    }
    if (select) {
        select.addEventListener('change', function () {
            current = 1;
            render();
        });
    }

    render();
}

document.addEventListener('DOMContentLoaded', function () {
    eauPaginateResults();

    var presets = document.querySelectorAll('.eau-preset');
    for (var i = 0; i < presets.length; i++) {
        presets[i].addEventListener('click', function () {
            eauApplyPreset(this.getAttribute('data-eau-preset') || '');
        });
    }

    var selectAll = document.querySelectorAll('.eau-group-all');
    for (var j = 0; j < selectAll.length; j++) {
        selectAll[j].addEventListener('click', function (e) {
            e.preventDefault();
            eauSetGroup(this.getAttribute('data-eau-group'), true);
        });
    }

    var selectNone = document.querySelectorAll('.eau-group-none');
    for (var k = 0; k < selectNone.length; k++) {
        selectNone[k].addEventListener('click', function (e) {
            e.preventDefault();
            eauSetGroup(this.getAttribute('data-eau-group'), false);
        });
    }
});
