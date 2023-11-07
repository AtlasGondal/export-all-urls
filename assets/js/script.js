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
    labelElement.setAttribute("onclick", `javascript: ${onclickFunction}; return false;`);
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
    console.log(document.getElementById('posts-from').value);
    if (document.getElementById('posts-from').value != "") {
        document.getElementById('posts-upto').setAttribute('min', document.getElementById('posts-from').value);
    }

}
