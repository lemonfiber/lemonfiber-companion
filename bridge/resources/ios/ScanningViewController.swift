import AVFoundation
import Foundation
import UIKit

/// The camera, on screen, reading one pairing code.
///
/// A screen of its own because that is what a camera is on this platform: it
/// takes the display, and the operator is either in it or they are not. It ends
/// on the first code it reads or on the operator dismissing it, and either way
/// the closure it was built with is what the waiting bridge call is listening
/// to.
///
/// **One code, then gone.** There is no continuous mode and no way to ask for
/// one. A scanner left open is a camera left on, and the one thing this
/// application reads is a pairing code that is spent the moment it is used.
///
/// **Nothing read reaches a log line.** The only value that passes through here
/// is the one being protected, so this file writes no log at all. That is a
/// promise a reader can check by reading it, which is the only kind worth
/// making about a camera.
///
/// Deliberately mirrors `ScanningActivity.kt`.
final class ScanningViewController: UIViewController, AVCaptureMetadataOutputObjectsDelegate {
    /// The sentence painted over the preview.
    private let prompt: String

    /// What the waiting call is told, once.
    private let answer: (String?) -> Void

    /// Whether something has already been handed back.
    ///
    /// The metadata delegate is called on a queue of its own and a dismissal
    /// arrives on the main one, so both can reach the end at once. Without this
    /// a code read at the moment somebody dismisses the screen would answer
    /// twice — and the second answer would be *the operator closed it*, about a
    /// scan that succeeded.
    private var done = false

    /// The camera feed, held so it can be stopped.
    private let session = AVCaptureSession()

    /// - Parameters:
    ///   - prompt: what the operator is told the camera is for.
    ///   - answer: what to tell the waiting call, once.
    init(prompt: String, answer: @escaping (String?) -> Void) {
        self.prompt = prompt
        self.answer = answer
        super.init(nibName: nil, bundle: nil)
    }

    /// Never built from a storyboard; this plugin ships no nibs.
    @available(*, unavailable)
    required init?(coder: NSCoder) {
        fatalError("ScanningViewController is built in code")
    }

    override func viewDidLoad() {
        super.viewDidLoad()

        view.backgroundColor = .black

        open()
        caption()
        closeControl()
    }

    override func viewDidDisappear(_ animated: Bool) {
        super.viewDidDisappear(animated)

        // Every way out of this screen, not only the control below: the
        // operator can swipe it away and the system can take it. Each of those
        // is the scanner closing with nothing read, and the call waiting on the
        // other side has to be told about all of them or it waits out its ten
        // minutes.
        finish(with: nil)
    }

    func metadataOutput(
        _ output: AVCaptureMetadataOutput,
        didOutput objects: [AVMetadataObject],
        from connection: AVCaptureConnection
    ) {
        // Only QR, because that is what lemonfiber's pairing material travels
        // in: the payload is a JSON object carrying an address and a certificate
        // digest, and the other symbologies cannot hold one. A reader that
        // accepted them all would answer with whatever else happened to be in
        // shot — a product code on a desk beside the stack is not pairing
        // material, and reporting it would dismiss the scanner and then report a
        // code that would not parse.
        let codes = objects.compactMap { $0 as? AVMetadataMachineReadableCodeObject }
        let found = codes.first { $0.type == .qr }?.stringValue

        guard let found else {
            return
        }

        finish(with: found)
    }

    /// Hand back whatever this scan came to, once.
    private func finish(with code: String?) {
        if done {
            return
        }

        done = true
        session.stopRunning()
        answer(code)
        presentingViewController?.dismiss(animated: true)
    }

    /// The operator's way out, for the frame where no code is coming.
    private func closeControl() {
        let close = UIButton(type: .system)

        close.setTitle(NSLocalizedString("Close", comment: "leave the scanner"), for: .normal)
        close.setTitleColor(.white, for: .normal)
        close.addAction(UIAction { [weak self] _ in self?.finish(with: nil) }, for: .touchUpInside)
        close.translatesAutoresizingMaskIntoConstraints = false

        view.addSubview(close)

        NSLayoutConstraint.activate([
            close.topAnchor.constraint(equalTo: view.safeAreaLayoutGuide.topAnchor, constant: margin),
            close.leadingAnchor.constraint(equalTo: view.safeAreaLayoutGuide.leadingAnchor, constant: margin),
        ])
    }

    /// The sentence painted over the preview.
    private func caption() {
        let line = UILabel()

        line.text = prompt
        line.textColor = .white
        line.backgroundColor = UIColor.black.withAlphaComponent(captionOpacity)
        line.textAlignment = .center
        line.numberOfLines = 0
        line.translatesAutoresizingMaskIntoConstraints = false

        view.addSubview(line)

        NSLayoutConstraint.activate([
            line.leadingAnchor.constraint(equalTo: view.leadingAnchor),
            line.trailingAnchor.constraint(equalTo: view.trailingAnchor),
            line.bottomAnchor.constraint(equalTo: view.bottomAnchor),
            line.heightAnchor.constraint(greaterThanOrEqualToConstant: captionHeight),
        ])
    }

    /// Start the camera and point the reader at what it sees.
    private func open() {
        guard
            let camera = AVCaptureDevice.default(for: .video),
            let feed = try? AVCaptureDeviceInput(device: camera),
            session.canAddInput(feed)
        else {
            return
        }

        let codes = AVCaptureMetadataOutput()

        session.addInput(feed)

        guard session.canAddOutput(codes) else {
            return
        }

        session.addOutput(codes)
        codes.setMetadataObjectsDelegate(self, queue: .main)
        codes.metadataObjectTypes = [.qr]

        let preview = AVCaptureVideoPreviewLayer(session: session)

        preview.frame = view.layer.bounds
        preview.videoGravity = .resizeAspectFill
        view.layer.addSublayer(preview)

        DispatchQueue.global(qos: .userInitiated).async { [session] in session.startRunning() }
    }

    /// The breathing room around the control, in points.
    private let margin: CGFloat = 16

    /// How tall the sentence's band is, in points.
    private let captionHeight: CGFloat = 96

    /// Black at two-thirds, so a light stack screen behind it stays readable.
    private let captionOpacity: CGFloat = 0.66
}
