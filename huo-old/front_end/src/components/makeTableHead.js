// 重置表格显示
export default function setTableStyle() {
    function setTheadCss() {
      const tbodyTr = $("#tBody table tbody tr").eq(0);
      const tbodyTds = [...tbodyTr.find("td")];
      const tHeadTds = $("#tHead table thead tr").eq(0).find("td");
      tbodyTds.forEach((td, key) => {
        const bodyTdWidth = $(td).css("width");
        tHeadTds.eq(key).css("min-width", bodyTdWidth);
      });
    }
    // copy thead
    if ($("#tHead").length === 0) {
      const myTbHead = document.createElement('div');
      myTbHead.id = "tHead";
      myTbHead.setAttribute("data-thead", "table");
      myTbHead.className = "t-head";
      $(".coin-in-table").eq(0).prepend(myTbHead);
      const catchTable = document.createElement("table");
      $("#tHead").append(catchTable);
      $("#tHead table").append($("#tBody table thead").clone());
      // set style
      setTheadCss();
    } else {
      setTheadCss();
    }
    // 滚动情况
    if (parseInt($("#tBody").css("height")) < parseInt($("#tBody table").css("height"))) {
      $("#tHead table td:last-child").css("paddingRight", "45px");
    }
  }
