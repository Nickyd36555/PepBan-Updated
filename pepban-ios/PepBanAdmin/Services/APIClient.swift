import Foundation

enum APIError: LocalizedError {
    case invalidURL
    case http(Int, String)
    case decode(Error)
    case network(Error)

    var errorDescription: String? {
        switch self {
        case .invalidURL:       return "Invalid URL"
        case .http(let c, let m): return "Server error \(c): \(m)"
        case .decode(let e):    return "Parse error: \(e.localizedDescription)"
        case .network(let e):   return e.localizedDescription
        }
    }
}

class APIClient {
    static let shared = APIClient()
    var token: String = ""

    private let base = "https://pepban.com/api/v1/admin"
    private let session: URLSession = {
        let cfg = URLSessionConfiguration.default
        cfg.timeoutIntervalForRequest = 30
        return URLSession(configuration: cfg)
    }()

    private var decoder: JSONDecoder {
        let d = JSONDecoder()
        d.keyDecodingStrategy = .convertFromSnakeCase
        return d
    }

    private func request<T: Decodable>(
        _ path: String,
        method: String = "GET",
        body: (any Encodable)? = nil,
        requiresAuth: Bool = true
    ) async throws -> T {
        guard let url = URL(string: "\(base)/\(path)") else { throw APIError.invalidURL }
        var req = URLRequest(url: url)
        req.httpMethod = method
        req.setValue("application/json", forHTTPHeaderField: "Content-Type")
        if requiresAuth && !token.isEmpty {
            req.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }
        if let body {
            let encoder = JSONEncoder()
            encoder.keyEncodingStrategy = .convertToSnakeCase
            req.httpBody = try encoder.encode(body)
        }
        let (data, response) = try await session.data(for: req)
        let status = (response as? HTTPURLResponse)?.statusCode ?? 0
        if status >= 400 {
            let msg = (try? decoder.decode(ErrorEnvelope.self, from: data))?.error ?? "Unknown error"
            throw APIError.http(status, msg)
        }
        do { return try decoder.decode(T.self, from: data) }
        catch { throw APIError.decode(error) }
    }

    private func get<T: Decodable>(_ path: String, query: [String: String] = [:]) async throws -> T {
        var comps = URLComponents(string: "\(base)/\(path)")!
        if !query.isEmpty {
            comps.queryItems = query.map { URLQueryItem(name: $0.key, value: $0.value) }
        }
        guard let url = comps.url else { throw APIError.invalidURL }
        var req = URLRequest(url: url)
        req.httpMethod = "GET"
        req.setValue("application/json", forHTTPHeaderField: "Content-Type")
        if !token.isEmpty { req.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization") }
        let (data, response) = try await session.data(for: req)
        let status = (response as? HTTPURLResponse)?.statusCode ?? 0
        if status >= 400 {
            let msg = (try? decoder.decode(ErrorEnvelope.self, from: data))?.error ?? "Unknown error"
            throw APIError.http(status, msg)
        }
        do { return try decoder.decode(T.self, from: data) }
        catch { throw APIError.decode(error) }
    }

    // MARK: - Auth

    struct LoginBody: Encodable { let password: String }
    struct LoginResponse: Decodable { let token: String; let expiresAt: String }
    struct SuccessResponse: Decodable { let success: Bool }
    struct ErrorEnvelope: Decodable { let error: String }

    func login(password: String) async throws -> LoginResponse {
        let savedToken = token
        token = ""
        defer { if token.isEmpty { token = savedToken } }
        return try await request("login", method: "POST", body: LoginBody(password: password), requiresAuth: false)
    }

    func logout() async throws {
        let _: SuccessResponse = try await request("logout", method: "POST")
    }

    // MARK: - Dashboard

    func dashboard() async throws -> DashboardStats {
        return try await request("dashboard")
    }

    // MARK: - Banned Customers

    func bannedList(page: Int = 1, search: String = "", status: String = "active") async throws -> BannedListResponse {
        var q: [String: String] = ["page": "\(page)", "status": status]
        if !search.isEmpty { q["search"] = search }
        return try await get("banned", query: q)
    }

    func bannedDetail(id: Int) async throws -> BannedDetailResponse {
        return try await request("banned/\(id)")
    }

    func updateBanned(id: Int, status: String? = nil, adminNotes: String? = nil) async throws {
        struct Body: Encodable { var status: String?; var adminNotes: String? }
        let _: SuccessResponse = try await request("banned/\(id)", method: "PATCH", body: Body(status: status, adminNotes: adminNotes))
    }

    func addBan(_ ban: NewBan) async throws -> Int {
        struct Res: Decodable { let success: Bool; let id: Int }
        let r: Res = try await request("banned", method: "POST", body: ban)
        return r.id
    }

    // MARK: - Clients

    func clientsList(page: Int = 1, search: String = "", status: String = "") async throws -> ClientsListResponse {
        var q: [String: String] = ["page": "\(page)"]
        if !search.isEmpty { q["search"] = search }
        if !status.isEmpty { q["status"] = status }
        return try await get("clients", query: q)
    }

    func clientDetail(id: Int) async throws -> ClientDetailResponse {
        return try await request("clients/\(id)")
    }

    func activateClient(id: Int) async throws {
        let _: SuccessResponse = try await request("clients/\(id)/activate", method: "POST")
    }

    func deactivateClient(id: Int) async throws {
        let _: SuccessResponse = try await request("clients/\(id)/deactivate", method: "POST")
    }

    // MARK: - Disputes

    func disputesList(page: Int = 1, status: String = "open") async throws -> DisputesListResponse {
        return try await get("disputes", query: ["page": "\(page)", "status": status])
    }

    func resolveDispute(id: Int) async throws {
        let _: SuccessResponse = try await request("disputes/\(id)/resolve", method: "POST")
    }

    func dismissDispute(id: Int) async throws {
        let _: SuccessResponse = try await request("disputes/\(id)/dismiss", method: "POST")
    }

    // MARK: - Audit

    func auditLog(page: Int = 1) async throws -> AuditListResponse {
        return try await get("audit", query: ["page": "\(page)"])
    }
}
