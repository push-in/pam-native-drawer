import UIKit
import PamNative

final class PamDrawerLayout: UIView, UIGestureRecognizerDelegate, NativePropertyView, NativeChildHost {
    var onProtocolEvent: ((Int, Data) -> Void)?
    func applyProtocolProperty(key: Int, value: WireValue?) {
        func number(_ fallback: Double) -> Double {
            switch value { case let .integer(v)?: return Double(v); case let .decimal(v)?: return v; default: return fallback }
        }
        func flag(_ fallback: Bool) -> Bool { if case let .flag(v)? = value { return v }; return fallback }
        switch key {
        case PamConstants.drawerOpen: setOpen(flag(false))
        case PamConstants.drawerPosition: setDrawerPosition(Int(number(1)))
        case PamConstants.drawerType: setDrawerType(Int(number(1)))
        case PamConstants.drawerWidth: setDrawerWidth(CGFloat(number(256)))
        case PamConstants.drawerOverlayColor: setOverlayColor(Int(number(855638016)))
        case PamConstants.drawerSwipeEnabled: setSwipeEnabled(flag(true))
        case PamConstants.drawerSwipeEdgeWidth: setSwipeEdgeWidth(CGFloat(number(32)))
        case PamConstants.drawerSwipeMinDistance: setSwipeMinDistance(CGFloat(number(56)))
        case PamConstants.drawerKeyboardDismissMode: setKeyboardDismissMode(Int(number(1)))
        case PamConstants.drawerHideStatusBarOnOpen: setHideStatusBarOnOpen(flag(false))
        case PamConstants.drawerStatusBarAnimation: setStatusBarAnimation(Int(number(1)))
        case PamConstants.drawerPermanentBreakpoint: setPermanentBreakpoint(CGFloat(number(840)))
        case PamConstants.onDrawerOpen: onOpen = value == nil ? nil : { [weak self] in self?.onProtocolEvent?(NativeViewEventKind.drawerOpen.rawValue, Data()) }
        case PamConstants.onDrawerClose: onClose = value == nil ? nil : { [weak self] in self?.onProtocolEvent?(NativeViewEventKind.drawerClose.rawValue, Data()) }
        default: break
        }
    }

    private let contentHost = UIView()
    private let overlayView = UIView()
    private let drawerHost = UIView()
    private var open = false
    private var drawerType = 1
    private var drawerPosition = 1
    private var preferredDrawerWidth: CGFloat = 256
    private var swipeEnabled = true
    private var swipeEdgeWidth: CGFloat = 32
    private var swipeMinDistance: CGFloat = 56
    private var keyboardDismissMode = 1
    private var permanentBreakpoint: CGFloat = 840
    private var hideStatusBarOnOpen = false
    private var statusBarAnimation = 1
    private var downX: CGFloat = 0
    private var gestureStartProgress: CGFloat = 0
    private var gestureInProgress = false

    private var onOpen: (() -> Void)?
    private var onClose: (() -> Void)?

    override init(frame: CGRect) {
        super.init(frame: frame)
        clipsToBounds = false
        addSubview(contentHost)
        overlayView.backgroundColor = drawerColor( 0x33000000)
        overlayView.alpha = 0
        overlayView.isUserInteractionEnabled = false
        addSubview(overlayView)
        addSubview(drawerHost)
        let pan = UIPanGestureRecognizer(target: self, action: #selector(handlePan(_:)))
        pan.cancelsTouchesInView = false
        pan.delegate = self
        addGestureRecognizer(pan)
        let tap = UITapGestureRecognizer(target: self, action: #selector(handleTap(_:)))
        tap.cancelsTouchesInView = false
        addGestureRecognizer(tap)
    }

    required init?(coder: NSCoder) {
        super.init(coder: coder)
    }

    func insert(_ view: UIView, index: Int) {
        if index <= 0 {
            if view.superview != contentHost {
                contentHost.addSubview(view)
            }
        } else {
            if view.superview != drawerHost {
                drawerHost.addSubview(view)
            }
        }
        setNeedsLayout()
        layoutIfNeeded()
        updateDrawer(animated: false)
    }

    func setOpen(_ value: Bool, animated: Bool = true) {
        guard open != value else {
            updateDrawer(animated: animated)
            return
        }
        open = value
        updateDrawer(animated: animated)
        if open {
            onOpen?()
        } else {
            onClose?()
        }
    }

    func setCallbacks(opened: (() -> Void)?, closed: (() -> Void)?) {
        onOpen = opened
        onClose = closed
    }

    func setDrawerType(_ value: Int) {
        drawerType = min(4, max(1, value))
        setNeedsLayout()
    }

    func setDrawerPosition(_ value: Int) {
        drawerPosition = min(3, max(1, value))
        setNeedsLayout()
    }

    func setDrawerWidth(_ value: CGFloat) {
        preferredDrawerWidth = min(640, max(200, value))
        setNeedsLayout()
    }

    func setOverlayColor(_ value: Int) {
        overlayView.backgroundColor = drawerColor( Int64(value))
    }

    func setSwipeEnabled(_ value: Bool) {
        swipeEnabled = value
    }

    func setSwipeEdgeWidth(_ value: CGFloat) {
        swipeEdgeWidth = min(256, max(0, value))
    }

    func setSwipeMinDistance(_ value: CGFloat) {
        swipeMinDistance = min(512, max(1, value))
    }

    func setKeyboardDismissMode(_ value: Int) {
        keyboardDismissMode = min(2, max(1, value))
    }

    func setPermanentBreakpoint(_ value: CGFloat) {
        permanentBreakpoint = max(0, value)
        setNeedsLayout()
    }

    func setHideStatusBarOnOpen(_ value: Bool) {
        hideStatusBarOnOpen = value
    }

    func setStatusBarAnimation(_ value: Int) {
        statusBarAnimation = min(3, max(1, value))
    }

    override func didMoveToWindow() {
        super.didMoveToWindow()
        if window == nil { layer.removeAllAnimations() }
    }

    override func layoutSubviews() {
        super.layoutSubviews()
        let type = resolvedType()
        applyProgress(type == 4 || open ? 1 : 0)
    }

    @objc private func handleTap(_ gesture: UITapGestureRecognizer) {
        guard gesture.state == .ended else { return }
        guard open && resolvedType() != 4 else { return }
        let point = gesture.location(in: self)
        if !drawerHost.frame.contains(point) {
            setOpen(false, animated: true)
        }
    }

    @objc private func handlePan(_ gesture: UIPanGestureRecognizer) {
        guard swipeEnabled && resolvedType() != 4 else { return }
        switch gesture.state {
        case .began:
            downX = gesture.location(in: self).x
            gestureStartProgress = open ? 1 : 0
            let edgeEligible = isRight
                ? downX >= bounds.width - swipeEdgeWidth
                : downX <= swipeEdgeWidth
            gestureInProgress = open || edgeEligible
            if gestureInProgress && keyboardDismissMode == 1 {
                endEditing(true)
            }
        case .changed:
            guard gestureInProgress else { return }
            let point = gesture.location(in: self)
            let deltaX = point.x - downX
            let directed = isRight ? -deltaX : deltaX
            let width = max(1, min(bounds.width, preferredDrawerWidth))
            applyProgress(max(0, min(1, gestureStartProgress + directed / width)))
        case .ended, .cancelled:
            let translation = gesture.translation(in: self).x
            gestureInProgress = false
            let directed = isRight ? -translation : translation
            if open {
                if directed <= -swipeMinDistance {
                    setOpen(false, animated: true)
                } else {
                    setOpen(true, animated: true)
                }
            } else {
                if directed >= swipeMinDistance {
                    setOpen(true, animated: true)
                } else {
                    setOpen(false, animated: true)
                }
            }
            gestureInProgress = false
        default:
            break
        }
    }

    override func gestureRecognizerShouldBegin(
        _ gestureRecognizer: UIGestureRecognizer
    ) -> Bool {
        guard swipeEnabled && resolvedType() != 4 else { return false }
        guard let pan = gestureRecognizer as? UIPanGestureRecognizer else {
            return true
        }
        let point = pan.location(in: self)
        if open && drawerHost.frame.contains(point) {
            return false
        }
        if !open {
            let edgeEligible = isRight
                ? point.x >= bounds.width - swipeEdgeWidth
                : point.x <= swipeEdgeWidth
            guard edgeEligible else { return false }
        }
        let velocity = pan.velocity(in: self)
        return abs(velocity.x) > abs(velocity.y)
    }

    func gestureRecognizer(
        _ gestureRecognizer: UIGestureRecognizer,
        shouldRecognizeSimultaneouslyWith otherGestureRecognizer: UIGestureRecognizer
    ) -> Bool {
        gestureRecognizer is UIPanGestureRecognizer
            || otherGestureRecognizer is UIPanGestureRecognizer
    }

    private func updateDrawer(animated: Bool) {
        if resolvedType() == 4 {
            open = true
        }
        guard animated, !UIAccessibility.isReduceMotionEnabled else {
            setNeedsLayout()
            layoutIfNeeded()
            return
        }
        UIView.animate(
            withDuration: 0.20,
            delay: 0,
            options: .curveEaseOut,
        ) {
            self.setNeedsLayout()
            self.layoutIfNeeded()
        }
    }

    private func applyProgress(_ rawProgress: CGFloat) {
        let type = resolvedType()
        let progress = max(0, min(1, rawProgress))
        let width = min(bounds.width, preferredDrawerWidth)
        let direction: CGFloat = isRight ? -1 : 1
        let openX = isRight ? bounds.width - width : 0
        let closedX = isRight ? bounds.width : -width
        let drawerStart: CGFloat
        switch type {
        case 2, 4:
            drawerStart = openX
        case 3:
            drawerStart = openX + (closedX - openX) * 0.35
        default:
            drawerStart = closedX
        }
        let drawerX = drawerStart + (openX - drawerStart) * progress
        drawerHost.frame = CGRect(
            x: drawerX,
            y: 0,
            width: width,
            height: bounds.height
        )
        let contentOffset = type == 4
            ? direction * width
            : ((type == 2 || type == 3) ? direction * width * progress : 0)
        contentHost.frame = bounds.offsetBy(dx: contentOffset, dy: 0)
        overlayView.frame = bounds
        overlayView.alpha = type == 4 ? 0 : progress
        overlayView.isUserInteractionEnabled = progress > 0
    }

    private var isRight: Bool {
        drawerPosition == 3 ||
            (drawerPosition == 1 && effectiveUserInterfaceLayoutDirection == .rightToLeft)
    }

    private func resolvedType() -> Int {
        permanentBreakpoint > 0 && bounds.width >= permanentBreakpoint ? 4 : drawerType
    }
}

private func drawerColor(_ value: Int64) -> UIColor {
    UIColor(red: CGFloat((value >> 16) & 255) / 255, green: CGFloat((value >> 8) & 255) / 255, blue: CGFloat(value & 255) / 255, alpha: CGFloat((value >> 24) & 255) / 255)
}
