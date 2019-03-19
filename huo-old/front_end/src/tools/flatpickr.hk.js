/* Mandarin locals for flatpickr */
var flatpickr = flatpickr || { l10ns: {} };
flatpickr.l10ns.zh = {};

flatpickr.l10ns.zh.weekdays = {
	shorthand: ["周日", "周壹", "周二", "周三", "周四", "周五", "周六"],
	longhand: ["星期日", "星期壹", "星期二", "星期三", "星期四", "星期五", "星期六"]
};

flatpickr.l10ns.zh.months = {
	shorthand: ["壹月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十壹月", "十二月"],
	longhand: ["壹月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十壹月", "十二月"]
};

flatpickr.l10ns.zh.rangeSeparator = " 至 ";
flatpickr.l10ns.zh.weekAbbreviation = "周";
flatpickr.l10ns.zh.scrollTitle = "滾動切換";
flatpickr.l10ns.zh.toggleTitle = "點擊切換 12/24 小時時制";

if (typeof module !== "undefined") module.exports = flatpickr.l10ns;
